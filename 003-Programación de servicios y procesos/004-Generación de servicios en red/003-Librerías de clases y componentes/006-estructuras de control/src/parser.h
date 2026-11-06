#ifndef NOEMA_PARSER_H
#define NOEMA_PARSER_H

#include "lexer.h"

#ifdef __cplusplus
extern "C" {
#endif

typedef struct Parser Parser;

/* =========================
   Statements
   ========================= */

typedef enum {
    STMT_IMPORT = 1,
    STMT_ASSIGN,
    STMT_CONST,
    STMT_CALL_PRINT,
    STMT_IF, STMT_WHILE, STMT_DO_WHILE, STMT_FOR, STMT_SWITCH, STMT_BREAK, STMT_CONTINUE
} StmtKind;

/* =========================
   Expressions (Phase 1)
   ========================= */

typedef enum {
    EXPR_LITERAL = 1,
    EXPR_VAR,
    EXPR_UNARY,
    EXPR_BINARY,
    EXPR_CALL
} ExprKind;

typedef enum {
    LIT_INT = 1,
    LIT_DECIMAL,
    LIT_STRING,
    LIT_BOOL,
    LIT_NULL
} LiteralKind;

typedef enum {
    OP_ADD = 1,
    OP_SUB,
    OP_MUL,
    OP_DIV,
    OP_MOD,

    OP_EQ,
    OP_NE,
    OP_LT,
    OP_LE,
    OP_GT,
    OP_GE,

    OP_AND,
    OP_OR,

    OP_NOT,
    OP_NEG
} ExprOp;

typedef struct Expr Expr;

struct Expr {
    ExprKind kind;
    int line;
    int col;

    union {
        struct {
            LiteralKind lit_kind;
            int int_value;                          // for int/bool
            double decimal_value;
            char text[NOEMA_TOKEN_VALUE_MAX];       // for string
        } lit;

        struct {
            char name[NOEMA_TOKEN_VALUE_MAX];       // variable name
        } var;

        struct {
            ExprOp op;
            Expr *rhs;
        } unary;

        struct {
            ExprOp op;
            Expr *lhs;
            Expr *rhs;
        } binary;
        struct { char name[NOEMA_TOKEN_VALUE_MAX]; Expr *arg; } call;

    } as;
};

/* =========================
   IF branches (si/aliosi/alio)
   ========================= */

typedef struct IfBranch {
    Expr *cond;                 // NULL means 'alio' (else)
    struct Stmt *body;          // linked list of statements in this block
    struct IfBranch *next;
} IfBranch;

/* =========================
   AST nodes
   ========================= */

typedef struct CaseBranch {
    Expr *match;
    struct Stmt *body;
    struct CaseBranch *next;
} CaseBranch;

typedef struct Stmt {
    StmtKind kind;
    int line, col;

    // import
    char module[NOEMA_TOKEN_VALUE_MAX];

    // assign
    char target[NOEMA_TOKEN_VALUE_MAX];
    Expr *value;

    // print call
    Expr *arg;

    // if
    IfBranch *if_branches;
    Expr *condition, *start, *end, *step;
    struct Stmt *body;
    CaseBranch *cases;
    char iterator[NOEMA_TOKEN_VALUE_MAX];

    struct Stmt *next;
} Stmt;

typedef struct {
    int ok;
    char message[512];
    Stmt *first;
    Stmt *last;
} ParseResult;

/* =========================
   API
   ========================= */

Parser*     parser_create(Lexer *lx);
void        parser_destroy(Parser *p);

ParseResult parser_parse_program(Parser *p);
void        parser_free_program(Stmt *first);

#ifdef __cplusplus
}
#endif

#endif
