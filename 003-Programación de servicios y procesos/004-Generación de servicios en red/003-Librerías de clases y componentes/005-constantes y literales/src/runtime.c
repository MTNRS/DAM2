// src/runtime.c
#include "runtime.h"
#include "parser.h"
#include "diag.h"

#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <errno.h>
#include <limits.h>
#include <math.h>
#include <ctype.h>

#define MAX_VARS 1000
#define NAME_MAX NOEMA_TOKEN_VALUE_MAX

/* ============================================================
   Helpers
   ============================================================ */

static char* xstrdup(const char *s) {
    if (!s) s = "";
    size_t n = strlen(s) + 1;
    char *p = (char*)malloc(n);
    if (!p) return NULL;
    memcpy(p, s, n);
    return p;
}

typedef struct {
    char name[NAME_MAX];
    Value v;
    int in_use;
    int is_constant;
} Var;

struct Runtime {
    Var vars[MAX_VARS];
};

static void value_free(Value *v) {
    if (!v) return;
    if (v->kind == VAL_STRING && v->string_value) {
        free(v->string_value);
        v->string_value = NULL;
    }
    v->kind = VAL_NULL;
    v->int_value = 0;
}

static Value value_copy(const Value *src) {
    Value out;
    memset(&out, 0, sizeof(out));
    out.kind = src->kind;
    out.int_value = src->int_value;
    out.decimal_value=src->decimal_value;
    if (src->kind == VAL_STRING) {
        out.string_value = xstrdup(src->string_value ? src->string_value : "");
        if (!out.string_value) out.string_value = xstrdup("");
    }
    return out;
}

static Var* find_var(Runtime *rt, const char *name) {
    for (int i = 0; i < MAX_VARS; i++) {
        if (rt->vars[i].in_use && strcmp(rt->vars[i].name, name) == 0) {
            return &rt->vars[i];
        }
    }
    return NULL;
}

static Var* upsert_var(Runtime *rt, const char *name) {
    Var *v = find_var(rt, name);
    if (v) return v;

    for (int i = 0; i < MAX_VARS; i++) {
        if (!rt->vars[i].in_use) {
            rt->vars[i].in_use = 1;
            rt->vars[i].is_constant = 0;
            strncpy(rt->vars[i].name, name, NAME_MAX - 1);
            rt->vars[i].name[NAME_MAX - 1] = '\0';
            rt->vars[i].v.kind = VAL_NULL;
            rt->vars[i].v.int_value = 0;
            rt->vars[i].v.string_value = NULL;
            return &rt->vars[i];
        }
    }
    return NULL;
}

static int value_truthy(const Value *v) {
    if (!v) return 0;
    switch (v->kind) {
        case VAL_NULL:   return 0;
        case VAL_BOOL:   return v->int_value ? 1 : 0;
        case VAL_INT:    return v->int_value != 0;
        case VAL_DECIMAL: return v->decimal_value != 0.0;
        case VAL_STRING: return (v->string_value && v->string_value[0]) ? 1 : 0;
        default:         return 0;
    }
}

static int values_equal(const Value *a, const Value *b) {
    if ((a->kind==VAL_INT || a->kind==VAL_DECIMAL) && (b->kind==VAL_INT || b->kind==VAL_DECIMAL)) {
        double x=a->kind==VAL_INT?a->int_value:a->decimal_value;
        double y=b->kind==VAL_INT?b->int_value:b->decimal_value;
        return x==y;
    }
    if (a->kind != b->kind) return 0;
    switch (a->kind) {
        case VAL_NULL: return 1;
        case VAL_INT:  return a->int_value == b->int_value;
        case VAL_DECIMAL: return a->decimal_value == b->decimal_value;
        case VAL_BOOL: return a->int_value == b->int_value;
        case VAL_STRING:
            if (!a->string_value && !b->string_value) return 1;
            if (!a->string_value || !b->string_value) return 0;
            return strcmp(a->string_value, b->string_value) == 0;
        default:
            return 0;
    }
}

static void runtime_error(char *err, int cap, const char *path, int line, int col, const char *msg) {
    diag_format(err, cap, path, line, col, "runtime error", msg);
}

/* ============================================================
   Value constructors (owned strings)
   ============================================================ */

static Value make_int(int x) {
    Value v;
    memset(&v, 0, sizeof(v));
    v.kind = VAL_INT;
    v.int_value = x;
    return v;
}

static Value make_decimal(double x) { Value v={0}; v.kind=VAL_DECIMAL; v.decimal_value=x; return v; }

static Value make_bool(int b) {
    Value v;
    memset(&v, 0, sizeof(v));
    v.kind = VAL_BOOL;
    v.int_value = b ? 1 : 0;
    return v;
}

static Value make_null(void) {
    Value v;
    memset(&v, 0, sizeof(v));
    v.kind = VAL_NULL;
    return v;
}

static Value make_string(const char *s) {
    Value v;
    memset(&v, 0, sizeof(v));
    v.kind = VAL_STRING;
    v.string_value = xstrdup(s ? s : "");
    if (!v.string_value) v.string_value = xstrdup("");
    return v;
}

static Value make_string_owned(char *s) {
    Value v;
    memset(&v, 0, sizeof(v));
    v.kind = VAL_STRING;
    v.string_value = s ? s : xstrdup("");
    if (!v.string_value) v.string_value = xstrdup("");
    return v;
}

/* ============================================================
   Expression evaluation
   ============================================================ */

static Value eval_expr(Runtime *rt, const Expr *e, const char *path, char *err, int cap);

static Value eval_unary(Runtime *rt, const Expr *e, const char *path, char *err, int cap) {
    Value rhs = eval_expr(rt, e->as.unary.rhs, path, err, cap);
    if (err[0]) { value_free(&rhs); return make_null(); }

    if (e->as.unary.op == OP_NOT) {
        int b = value_truthy(&rhs) ? 0 : 1;
        value_free(&rhs);
        return make_bool(b);
    }

    if (e->as.unary.op == OP_NEG) {
        if (rhs.kind == VAL_DECIMAL) { Value out=make_decimal(-rhs.decimal_value); value_free(&rhs); return out; }
        if (rhs.kind != VAL_INT) {
            runtime_error(err, cap, path, e->line, e->col, "unary '-' expects integer");
            value_free(&rhs);
            return make_null();
        }
        int r = -rhs.int_value;
        value_free(&rhs);
        return make_int(r);
    }

    runtime_error(err, cap, path, e->line, e->col, "unsupported unary operator");
    value_free(&rhs);
    return make_null();
}

static Value eval_binary(Runtime *rt, const Expr *e, const char *path, char *err, int cap) {
    /* short-circuit for et/aut */
    if (e->as.binary.op == OP_AND) {
        Value lhs = eval_expr(rt, e->as.binary.lhs, path, err, cap);
        if (err[0]) { value_free(&lhs); return make_null(); }
        if (!value_truthy(&lhs)) {
            value_free(&lhs);
            return make_bool(0);
        }
        value_free(&lhs);
        Value rhs = eval_expr(rt, e->as.binary.rhs, path, err, cap);
        if (err[0]) { value_free(&rhs); return make_null(); }
        int b = value_truthy(&rhs);
        value_free(&rhs);
        return make_bool(b);
    }

    if (e->as.binary.op == OP_OR) {
        Value lhs = eval_expr(rt, e->as.binary.lhs, path, err, cap);
        if (err[0]) { value_free(&lhs); return make_null(); }
        if (value_truthy(&lhs)) {
            value_free(&lhs);
            return make_bool(1);
        }
        value_free(&lhs);
        Value rhs = eval_expr(rt, e->as.binary.rhs, path, err, cap);
        if (err[0]) { value_free(&rhs); return make_null(); }
        int b = value_truthy(&rhs);
        value_free(&rhs);
        return make_bool(b);
    }

    Value lhs = eval_expr(rt, e->as.binary.lhs, path, err, cap);
    if (err[0]) { value_free(&lhs); return make_null(); }

    Value rhs = eval_expr(rt, e->as.binary.rhs, path, err, cap);
    if (err[0]) { value_free(&lhs); value_free(&rhs); return make_null(); }

    /* Numeric promotion: entero + decimal => decimal. */
    if ((lhs.kind==VAL_INT || lhs.kind==VAL_DECIMAL) && (rhs.kind==VAL_INT || rhs.kind==VAL_DECIMAL) &&
        (e->as.binary.op==OP_ADD || e->as.binary.op==OP_SUB || e->as.binary.op==OP_MUL || e->as.binary.op==OP_DIV ||
         e->as.binary.op==OP_LT || e->as.binary.op==OP_LE || e->as.binary.op==OP_GT || e->as.binary.op==OP_GE)) {
        double a=lhs.kind==VAL_INT?lhs.int_value:lhs.decimal_value;
        double b=rhs.kind==VAL_INT?rhs.int_value:rhs.decimal_value;
        ExprOp op=e->as.binary.op;
        if (op==OP_DIV && b==0) {runtime_error(err,cap,path,e->line,e->col,"division por cero");value_free(&lhs);value_free(&rhs);return make_null();}
        Value out=make_null();
        if(op==OP_LT)out=make_bool(a<b);
        else if(op==OP_LE)out=make_bool(a<=b);
        else if(op==OP_GT)out=make_bool(a>b);
        else if(op==OP_GE)out=make_bool(a>=b);
        else if(lhs.kind==VAL_DECIMAL || rhs.kind==VAL_DECIMAL) {
            out=make_decimal(op==OP_ADD?a+b:op==OP_SUB?a-b:op==OP_MUL?a*b:a/b);
        } else if(op==OP_ADD)out=make_int((int)(a+b));
        else if(op==OP_SUB)out=make_int((int)(a-b));
        else if(op==OP_MUL)out=make_int((int)(a*b));
        else out=make_int((int)(a/b));
        value_free(&lhs);value_free(&rhs);return out;
    }
    /* arithmetic + concat */
    if (e->as.binary.op == OP_ADD) {
        if (lhs.kind == VAL_INT && rhs.kind == VAL_INT) {
            Value out = make_int(lhs.int_value + rhs.int_value);
            value_free(&lhs); value_free(&rhs);
            return out;
        }
        if (lhs.kind == VAL_STRING && rhs.kind == VAL_STRING) {
            const char *a = lhs.string_value ? lhs.string_value : "";
            const char *b = rhs.string_value ? rhs.string_value : "";
            size_t na = strlen(a), nb = strlen(b);
            char *buf = (char*)malloc(na + nb + 1);
            if (!buf) {
                runtime_error(err, cap, path, e->line, e->col, "out of memory concatenating strings");
                value_free(&lhs); value_free(&rhs);
                return make_null();
            }
            memcpy(buf, a, na);
            memcpy(buf + na, b, nb);
            buf[na + nb] = '\0';
            Value out = make_string_owned(buf);
            value_free(&lhs); value_free(&rhs);
            return out;
        }
        runtime_error(err, cap, path, e->line, e->col, "operator '+' expects int+int or string+string");
        value_free(&lhs); value_free(&rhs);
        return make_null();
    }

    if (e->as.binary.op == OP_SUB || e->as.binary.op == OP_MUL ||
        e->as.binary.op == OP_DIV || e->as.binary.op == OP_MOD) {

        if (lhs.kind != VAL_INT || rhs.kind != VAL_INT) {
            runtime_error(err, cap, path, e->line, e->col, "arithmetic operators expect integers");
            value_free(&lhs); value_free(&rhs);
            return make_null();
        }

        if (e->as.binary.op == OP_SUB) {
            Value out = make_int(lhs.int_value - rhs.int_value);
            value_free(&lhs); value_free(&rhs);
            return out;
        }

        if (e->as.binary.op == OP_MUL) {
            Value out = make_int(lhs.int_value * rhs.int_value);
            value_free(&lhs); value_free(&rhs);
            return out;
        }

        if (e->as.binary.op == OP_DIV) {
            if (rhs.int_value == 0) {
                runtime_error(err, cap, path, e->line, e->col, "division by zero");
                value_free(&lhs); value_free(&rhs);
                return make_null();
            }
            Value out = make_int(lhs.int_value / rhs.int_value);
            value_free(&lhs); value_free(&rhs);
            return out;
        }

        if (e->as.binary.op == OP_MOD) {
            if (rhs.int_value == 0) {
                runtime_error(err, cap, path, e->line, e->col, "modulo by zero");
                value_free(&lhs); value_free(&rhs);
                return make_null();
            }
            Value out = make_int(lhs.int_value % rhs.int_value);
            value_free(&lhs); value_free(&rhs);
            return out;
        }
    }

    /* equality / comparisons */
    if (e->as.binary.op == OP_EQ || e->as.binary.op == OP_NE) {
        int eq = values_equal(&lhs, &rhs);
        Value out = make_bool(e->as.binary.op == OP_EQ ? eq : !eq);
        value_free(&lhs); value_free(&rhs);
        return out;
    }

    if (e->as.binary.op == OP_LT || e->as.binary.op == OP_LE ||
        e->as.binary.op == OP_GT || e->as.binary.op == OP_GE) {

        if (lhs.kind != VAL_INT || rhs.kind != VAL_INT) {
            runtime_error(err, cap, path, e->line, e->col, "comparison operators expect integers");
            value_free(&lhs); value_free(&rhs);
            return make_null();
        }

        int ok = 0;
        if (e->as.binary.op == OP_LT) ok = (lhs.int_value < rhs.int_value);
        if (e->as.binary.op == OP_LE) ok = (lhs.int_value <= rhs.int_value);
        if (e->as.binary.op == OP_GT) ok = (lhs.int_value > rhs.int_value);
        if (e->as.binary.op == OP_GE) ok = (lhs.int_value >= rhs.int_value);

        Value out = make_bool(ok);
        value_free(&lhs); value_free(&rhs);
        return out;
    }

    runtime_error(err, cap, path, e->line, e->col, "unsupported binary operator");
    value_free(&lhs); value_free(&rhs);
    return make_null();
}

static Value eval_expr(Runtime *rt, const Expr *e, const char *path, char *err, int cap) {
    Value v;
    memset(&v, 0, sizeof(v));
    v.kind = VAL_NULL;

    if (!e) {
        runtime_error(err, cap, path, 0, 0, "null expression");
        return v;
    }

    switch (e->kind) {
        case EXPR_LITERAL:
            if (e->as.lit.lit_kind == LIT_DECIMAL) return make_decimal(e->as.lit.decimal_value);
            if (e->as.lit.lit_kind == LIT_INT)   return make_int(e->as.lit.int_value);
            if (e->as.lit.lit_kind == LIT_BOOL)  return make_bool(e->as.lit.int_value ? 1 : 0);
            if (e->as.lit.lit_kind == LIT_NULL)  return make_null();
            if (e->as.lit.lit_kind == LIT_STRING) return make_string(e->as.lit.text);
            runtime_error(err, cap, path, e->line, e->col, "unknown literal kind");
            return make_null();

        case EXPR_VAR: {
            Var *var = find_var(rt, e->as.var.name);
            if (!var) {
                char msg[320];
                snprintf(msg, sizeof(msg), "undefined variable '%s'", e->as.var.name);
                runtime_error(err, cap, path, e->line, e->col, msg);
                return make_null();
            }
            return value_copy(&var->v);
        }

        case EXPR_CALL: {
            Value arg=eval_expr(rt,e->as.call.arg,path,err,cap);
            if(err[0]) {value_free(&arg);return make_null();}
            const char *name=e->as.call.name;
            Value out=make_null();
            char buf[128]; const char *src=NULL;
            if(!strcmp(name,"tipo")) {
                const char *t=arg.kind==VAL_INT?"entero":arg.kind==VAL_DECIMAL?"decimal":arg.kind==VAL_STRING?"texto":arg.kind==VAL_BOOL?"booleano":"nulo";
                out=make_string(t);
            } else if(!strcmp(name,"texto")) {
                if(arg.kind==VAL_STRING)out=value_copy(&arg);
                else { if(arg.kind==VAL_INT)snprintf(buf,sizeof(buf),"%d",arg.int_value);
                    else if(arg.kind==VAL_DECIMAL)snprintf(buf,sizeof(buf),"%.15g",arg.decimal_value);
                    else snprintf(buf,sizeof(buf),"%s",arg.kind==VAL_BOOL?(arg.int_value?"verdadero":"falso"):"nulo");
                    out=make_string(buf); }
            } else if(!strcmp(name,"booleano"))out=make_bool(value_truthy(&arg));
            else if(!strcmp(name,"entero") || !strcmp(name,"decimal")) {
                int want_int=!strcmp(name,"entero"); double number=0; int valid=1;
                if(arg.kind==VAL_INT || arg.kind==VAL_BOOL)number=arg.int_value;
                else if(arg.kind==VAL_DECIMAL)number=arg.decimal_value;
                else if(arg.kind==VAL_STRING) {
                    src=arg.string_value?arg.string_value:"";
                    while(isspace((unsigned char)*src))src++;
                    if(!*src)valid=0;
                    else {char *end=NULL;errno=0;number=strtod(src,&end);
                        if(errno==ERANGE || end==src || !isfinite(number))valid=0;
                        while(end && isspace((unsigned char)*end))end++;
                        if(end && *end)valid=0;
                        if(want_int && strchr(src,'.'))valid=0;
                    }
                } else valid=0;
                if(want_int && (number>INT_MAX || number<INT_MIN))valid=0;
                if(!valid)runtime_error(err,cap,path,e->line,e->col,"conversion numerica no valida");
                else out=want_int?make_int((int)number):make_decimal(number);
            }
            value_free(&arg);return out;
        }
        case EXPR_UNARY:
            return eval_unary(rt, e, path, err, cap);

        case EXPR_BINARY:
            return eval_binary(rt, e, path, err, cap);

        default:
            runtime_error(err, cap, path, e->line, e->col, "unsupported expression kind");
            return make_null();
    }
}

/* ============================================================
   Statement execution (Phase 2: IF)
   ============================================================ */

static void print_value(const Value *v) {
    switch (v->kind) {
        case VAL_STRING: printf("%s\n", v->string_value ? v->string_value : ""); break;
        case VAL_INT:    printf("%d\n", v->int_value); break;
        case VAL_DECIMAL: printf("%.15g\n",v->decimal_value); break;
        case VAL_BOOL:   printf("%s\n", v->int_value ? "verdadero" : "falso"); break;
        case VAL_NULL:
        default:         printf("nulo\n"); break;
    }
}

static int exec_block(Runtime *rt, Stmt *first, const char *path, char *err, int cap);

static int exec_if(Runtime *rt, Stmt *s, const char *path, char *err, int cap) {
    for (IfBranch *b = s->if_branches; b; b = b->next) {
        if (b->cond == NULL) {
            return exec_block(rt, b->body, path, err, cap);
        }

        Value cv = eval_expr(rt, b->cond, path, err, cap);
        if (err[0]) { value_free(&cv); return 0; }

        int take = value_truthy(&cv);
        value_free(&cv);

        if (take) return exec_block(rt, b->body, path, err, cap);
    }
    return 1;
}

static int exec_block(Runtime *rt, Stmt *first, const char *path, char *err, int cap) {
    for (Stmt *s = first; s; s = s->next) {

        switch (s->kind) {
            case STMT_IMPORT:
                /* still no-op (sonus is builtin for now) */
                break;

            case STMT_ASSIGN:
            case STMT_CONST: {
                Var *existing = find_var(rt, s->target);
                if (existing && (existing->is_constant || s->kind == STMT_CONST)) {
                    char msg[350];
                    snprintf(msg, sizeof(msg), "no se puede reasignar la constante o redeclarar '%s'", s->target);
                    runtime_error(err, cap, path, s->line, s->col, msg);
                    return 0;
                }
                Value rhs = eval_expr(rt, s->value, path, err, cap);
                if (err[0]) { value_free(&rhs); return 0; }
                Var *var = upsert_var(rt, s->target);
                if (!var) {
                    value_free(&rhs);
                    runtime_error(err, cap, path, s->line, s->col, "too many variables");
                    return 0;
                }

                value_free(&var->v);
                var->v = rhs;          /* store owned value (do NOT free rhs after) */
                if (s->kind == STMT_CONST) var->is_constant = 1;
                break;
            }

            case STMT_CALL_PRINT: {
                Value v = eval_expr(rt, s->arg, path, err, cap);
                if (err[0]) { value_free(&v); return 0; }
                print_value(&v);
                value_free(&v);
                break;
            }

            case STMT_IF:
                if (!exec_if(rt, s, path, err, cap)) return 0;
                break;

            default:
                runtime_error(err, cap, path, s->line, s->col, "unknown statement kind");
                return 0;
        }
    }
    return 1;
}

/* ============================================================
   Public API
   ============================================================ */

Runtime* runtime_create(void) {
    Runtime *rt = (Runtime*)calloc(1, sizeof(Runtime));
    return rt;
}

void runtime_destroy(Runtime *rt) {
    if (!rt) return;
    for (int i = 0; i < MAX_VARS; i++) {
        if (rt->vars[i].in_use) {
            value_free(&rt->vars[i].v);
            rt->vars[i].in_use = 0;
        }
    }
    free(rt);
}

int runtime_exec(Runtime *rt, Stmt *program, const char *path, char *err_out, int err_cap) {
    if (!rt) return 0;
    if (!err_out || err_cap <= 0) return 0;

    err_out[0] = '\0';
    if (!path || !path[0]) path = "<input>";

    return exec_block(rt, program, path, err_out, err_cap);
}
