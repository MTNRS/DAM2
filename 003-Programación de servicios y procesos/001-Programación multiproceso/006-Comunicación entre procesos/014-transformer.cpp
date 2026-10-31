#include <algorithm>
#include <cmath>
#include <cstdint>
#include <fstream>
#include <iomanip>
#include <iostream>
#include <random>
#include <stdexcept>
#include <string>
#include <vector>
using namespace std;

constexpr int VOC=256, D=32, FF=64, CTX=32;
constexpr double LR=0.0005;
mt19937 rng(12345);
struct Param {
    vector<double> w,g,m,v;
    Param(size_t n=0, double scale=0.02):w(n),g(n,0),m(n,0),v(n,0){
        normal_distribution<double> dist(0,scale);
        for(auto &x:w)x=dist(rng);
    }
    void zero(){fill(g.begin(),g.end(),0);}
    void adam(uint64_t step){
        double b1=1-pow(0.9,(double)step), b2=1-pow(0.999,(double)step);
        for(size_t i=0;i<w.size();++i){
            double grad=clamp(g[i],-1.0,1.0);
            m[i]=0.9*m[i]+0.1*grad;
            v[i]=0.999*v[i]+0.001*grad*grad;
            w[i]-=LR*(m[i]/b1)/(sqrt(v[i]/b2)+1e-8);
        }
    }
};
using Vec=vector<double>;
using Mat=vector<Vec>;
Vec zeros(int n){return Vec(n,0.0);}
Mat mat(int rows,int cols){return Mat(rows,Vec(cols,0.0));}
// Row-major matrix: [input dimension, output dimension]
Vec linear(const Vec &x,const Param &w,int out){
    Vec y(out,0);
    for(int i=0;i<(int)x.size();++i)
        for(int j=0;j<out;++j)y[j]+=x[i]*w.w[(size_t)i*out+j];
    return y;
}
void linear_back(const Vec &x,const Vec &dy,Param &w,Vec &dx){
    int out=(int)dy.size();
    for(int i=0;i<(int)x.size();++i)
        for(int j=0;j<out;++j){
            size_t k=(size_t)i*out+j;
            w.g[k]+=x[i]*dy[j];
            dx[i]+=w.w[k]*dy[j];
        }
}
struct Cache {
    Mat x,q,k,v,attention,probs,context,attout,h,hidden,ff,z;
};
struct Model {
    Param emb{VOC*D}, pos{CTX*D}, wq{D*D},wk{D*D},wv{D*D},wo{D*D},
          w1{D*FF},w2{FF*D},out{D*VOC};
    vector<Param*> params(){return {&emb,&pos,&wq,&wk,&wv,&wo,&w1,&w2,&out};}
    uint64_t step=0;
    void zero(){for(auto p:params())p->zero();}
    void update(){++step;for(auto p:params())p->adam(step);}
    Cache forward(const vector<unsigned char>& tokens){
        int n=(int)tokens.size();
        Cache c;
        c.x=mat(n,D);c.q=mat(n,D);c.k=mat(n,D);c.v=mat(n,D);
        c.attention=mat(n,n);c.probs=mat(n,n);c.context=mat(n,D);
        c.attout=mat(n,D);c.h=mat(n,D);c.hidden=mat(n,FF);
        c.ff=mat(n,D);c.z=mat(n,D);
        for(int t=0;t<n;++t){
            for(int d=0;d<D;++d)
                c.x[t][d]=emb.w[(size_t)tokens[t]*D+d]+pos.w[(size_t)t*D+d];
            c.q[t]=linear(c.x[t],wq,D);
            c.k[t]=linear(c.x[t],wk,D);
            c.v[t]=linear(c.x[t],wv,D);
        }
        for(int t=0;t<n;++t){
            double mx=-1e100;
            for(int j=0;j<=t;++j){
                double s=0;
                for(int d=0;d<D;++d)s+=c.q[t][d]*c.k[j][d];
                c.attention[t][j]=s/sqrt((double)D);
                mx=max(mx,c.attention[t][j]);
            }
            double sum=0;
            for(int j=0;j<=t;++j){
                c.probs[t][j]=exp(c.attention[t][j]-mx);
                sum+=c.probs[t][j];
            }
            for(int j=0;j<=t;++j){
                c.probs[t][j]/=sum;
                for(int d=0;d<D;++d)c.context[t][d]+=c.probs[t][j]*c.v[j][d];
            }
            c.attout[t]=linear(c.context[t],wo,D);
            for(int d=0;d<D;++d)c.h[t][d]=c.x[t][d]+c.attout[t][d];
            c.hidden[t]=linear(c.h[t],w1,FF);
            for(double &v:c.hidden[t])v=max(0.0,v);
            c.ff[t]=linear(c.hidden[t],w2,D);
            for(int d=0;d<D;++d)c.z[t][d]=c.h[t][d]+c.ff[t][d];
        }
        return c;
    }
    double train(const vector<unsigned char>& seq){
        int n=(int)seq.size()-1;
        vector<unsigned char> input(seq.begin(),seq.end()-1);
        Cache c=forward(input);
        zero();
        Mat dz=mat(n,D),dh=mat(n,D),dx=mat(n,D),dc=mat(n,D);
        Mat dq=mat(n,D),dk=mat(n,D),dv=mat(n,D);
        Mat dp=mat(n,n),ds=mat(n,n);
        double loss=0;
        for(int t=0;t<n;++t){
            Vec logits=linear(c.z[t],out,VOC);
            double mx=*max_element(logits.begin(),logits.end()),sum=0;
            for(double &v:logits){v=exp(v-mx);sum+=v;}
            for(double &v:logits)v/=sum;
            loss-=log(max(logits[seq[t+1]],1e-30));
            logits[seq[t+1]]-=1.0;
            for(double &v:logits)v/=n;
            linear_back(c.z[t],logits,out,dz[t]);
        }
        // Backpropagate feed-forward and residual
        for(int t=0;t<n;++t){
            dh[t]=dz[t];
            Vec dff=dz[t],dhidden(FF,0);
            linear_back(c.hidden[t],dff,w2,dhidden);
            for(int j=0;j<FF;++j)if(c.hidden[t][j]<=0)dhidden[j]=0;
            linear_back(c.h[t],dhidden,w1,dh[t]);
            for(int d=0;d<D;++d)dx[t][d]+=dh[t][d];
            linear_back(c.context[t],dh[t],wo,dc[t]);
        }
        // Attention backwards: context = softmax(scores) * V
        for(int t=0;t<n;++t){
            for(int j=0;j<=t;++j){
                for(int d=0;d<D;++d){
                    dp[t][j]+=dc[t][d]*c.v[j][d];
                    dv[j][d]+=c.probs[t][j]*dc[t][d];
                }
            }
            double weighted=0;
            for(int j=0;j<=t;++j)weighted+=dp[t][j]*c.probs[t][j];
            for(int j=0;j<=t;++j){
                ds[t][j]=c.probs[t][j]*(dp[t][j]-weighted)/sqrt((double)D);
                for(int d=0;d<D;++d){
                    dq[t][d]+=ds[t][j]*c.k[j][d];
                    dk[j][d]+=ds[t][j]*c.q[t][d];
                }
            }
        }
        for(int t=0;t<n;++t){
            linear_back(c.x[t],dq[t],wq,dx[t]);
            linear_back(c.x[t],dk[t],wk,dx[t]);
            linear_back(c.x[t],dv[t],wv,dx[t]);
            for(int d=0;d<D;++d){
                emb.g[(size_t)input[t]*D+d]+=dx[t][d];
                pos.g[(size_t)t*D+d]+=dx[t][d];
            }
        }
        update();
        return loss/n;
    }
    Vec next(const vector<unsigned char>& tokens){
        Cache c=forward(tokens);
        Vec p=linear(c.z.back(),out,VOC);
        double mx=*max_element(p.begin(),p.end()),sum=0;
        for(double &v:p){v=exp(v-mx);sum+=v;}
        for(double &v:p)v/=sum;
        return p;
    }
    void save(const string& path){
        ofstream f(path,ios::binary);
        if(!f)throw runtime_error("No se puede guardar "+path);
        const uint64_t magic=0x5452414e53463131ULL;
        f.write((char*)&magic,sizeof(magic));f.write((char*)&step,sizeof(step));
        for(auto p:params())for(auto arr:{&p->w,&p->m,&p->v})
            f.write((char*)arr->data(),arr->size()*sizeof(double));
        if(!f)throw runtime_error("Error guardando modelo");
    }
    void load(const string& path){
        ifstream f(path,ios::binary);
        if(!f)throw runtime_error("No se encuentra "+path);
        uint64_t magic=0;
        f.read((char*)&magic,sizeof(magic));
        if(magic!=0x5452414e53463131ULL)throw runtime_error("Formato de modelo incompatible");
        f.read((char*)&step,sizeof(step));
        for(auto p:params())for(auto arr:{&p->w,&p->m,&p->v})
            f.read((char*)arr->data(),arr->size()*sizeof(double));
        if(!f)throw runtime_error("Modelo incompleto");
    }
};
int main(int argc,char**argv){
    try{
        if(argc<2){
            cerr<<"Uso:\n  "<<argv[0]<<" entrenar archivo.txt [pasos]\n"
                <<"  "<<argv[0]<<" continuar archivo.txt [pasos]\n"
                <<"  "<<argv[0]<<" generar \"texto inicial\" [bytes]\n";
            return 1;
        }
        string mode=argv[1];
        Model model;
        if(mode=="entrenar"||mode=="continuar"){
            if(argc<3)throw runtime_error("Falta archivo TXT");
            if(mode=="continuar")model.load("modelo.bin");
            ifstream file(argv[2],ios::binary|ios::ate);
            if(!file)throw runtime_error("No se puede abrir el TXT");
            streamoff size=file.tellg();
            if(size<CTX+1)throw runtime_error("El TXT necesita al menos 33 bytes");
            file.seekg(0);
            int pasos=argc>3?stoi(argv[3]):10000;
            if(pasos<=0)throw runtime_error("Pasos debe ser positivo");
            uniform_int_distribution<uint64_t> sample(0,(uint64_t)size-CTX-1);
            vector<unsigned char> seq(CTX+1);
            cout<<"Archivo: "<<size<<" bytes | Contexto: "<<CTX
                <<" | Pasos: "<<pasos<<" | Inicio: "<<model.step<<"\n";
            double avg=0;
            for(int i=1;i<=pasos;++i){
                uint64_t start=sample(rng);
                file.clear();file.seekg((streamoff)start,ios::beg);
                file.read((char*)seq.data(),seq.size());
                if(!file)throw runtime_error("Error leyendo muestra del TXT");
                double loss=model.train(seq);
                avg+=loss;
                if(i%100==0||i==pasos){
                    cout<<"Paso "<<i<<" / "<<pasos<<" | loss "
                        <<fixed<<setprecision(5)<<avg/(i%100==0?100:i%100)<<"\n";
                    avg=0;
                }
                if(i%1000==0){model.save("modelo.bin");cout<<"Checkpoint guardado\n";}
            }
            model.save("modelo.bin");
            cout<<"Entrenamiento terminado: modelo.bin\n";
        }else if(mode=="generar"){
            model.load("modelo.bin");
            string prompt=argc>2?argv[2]:"";
            int count=argc>3?stoi(argv[3]):300;
            if(count<0||count>100000)throw runtime_error("Numero de bytes invalido");
            vector<unsigned char> context(prompt.begin(),prompt.end());
            if(context.empty())context.push_back(' ');
            cout<<prompt<<flush;
            for(int i=0;i<count;++i){
                int start=max(0,(int)context.size()-CTX);
                vector<unsigned char> window(context.begin()+start,context.end());
                Vec p=model.next(window);
                // Temperature 0.8
                double total=0;
                for(double &v:p){v=pow(max(v,1e-20),1.0/0.8);total+=v;}
                uniform_real_distribution<double> uniform(0,total);
                double r=uniform(rng);
                int token=255;
                for(int j=0;j<VOC;++j){r-=p[j];if(r<=0){token=j;break;}}
                context.push_back((unsigned char)token);
                cout<<(char)token<<flush;
            }
            cout<<"\n";
        }else throw runtime_error("Comando desconocido: "+mode);
    }catch(const exception& e){cerr<<"Error: "<<e.what()<<"\n";return 1;}
}
