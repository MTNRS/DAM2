#include <algorithm>
#include <chrono>
#include <cmath>
#include <cstdint>
#include <fstream>
#include <iomanip>
#include <iostream>
#include <random>
#include <vector>
#include <X11/Xlib.h>
#include <X11/Xutil.h>
#include <cstring>
using namespace std;
constexpr double PI=3.14159265358979323846;
struct V {double x=0,y=0,z=0; V operator+(V b)const{return {x+b.x,y+b.y,z+b.z};} V operator-(V b)const{return {x-b.x,y-b.y,z-b.z};} V operator*(double s)const{return {x*s,y*s,z*s};} V operator/(double s)const{return *this*(1/s);} V mul(V b)const{return {x*b.x,y*b.y,z*b.z};}};
double dot(V a,V b){return a.x*b.x+a.y*b.y+a.z*b.z;} V cross(V a,V b){return {a.y*b.z-a.z*b.y,a.z*b.x-a.x*b.z,a.x*b.y-a.y*b.x};} V norm(V a){return a/sqrt(dot(a,a));} double lum(V a){return .2126*a.x+.7152*a.y+.0722*a.z;}
struct Ray{V o,d;}; struct Hit{double t=1e30; V p,n,c,e; bool ok=false;};
struct Quad{V p,u,v,c,e;}; vector<Quad> scene;
void quad(V p,V u,V v,V c,V e={}){scene.push_back({p,u,v,c,e});}
void box(V a,V b,V c){V x{b.x-a.x,0,0},y{0,b.y-a.y,0},z{0,0,b.z-a.z}; quad(a,x,y,c);quad(a+z,x,y,c);quad(a,x,z,c);quad(a+y,x,z,c);quad(a,y,z,c);quad(a+x,y,z,c);}
void setup(){V w{.73,.73,.73};quad({-1,0,0},{0,2,0},{0,0,2},{.75,.12,.12});quad({1,0,0},{0,0,2},{0,2,0},{.12,.7,.15});quad({-1,0,2},{2,0,0},{0,2,0},w);quad({-1,0,0},{2,0,0},{0,0,2},w);quad({-1,2,0},{0,0,2},{2,0,0},w);quad({-.34,1.999,.68},{.68,0,0},{0,0,.65},{0,0,0},{16,15,13});box({-.76,0,.88},{-.13,.67,1.54},{.76,.76,.76});box({.18,0,.45},{.76,1.22,1.10},{.76,.76,.76});}
Hit intersect(Ray r){Hit h;for(auto q:scene){V n=norm(cross(q.u,q.v));double den=dot(n,r.d);if(abs(den)<1e-9)continue;double t=dot(q.p-r.o,n)/den;if(t<1e-5||t>=h.t)continue;V p=r.o+r.d*t,d=p-q.p;double uu=dot(q.u,q.u),vv=dot(q.v,q.v);double a=dot(d,q.u)/uu,b=dot(d,q.v)/vv;if(a<0||a>1||b<0||b>1)continue;h={t,p,den<0?n:n*-1,q.c,q.e,true};}return h;}
struct Sample{vector<double> x; V value;double weight=0;int pixel=0;};
mt19937_64 rng; uniform_real_distribution<double> uniform(0,1);
double rnd(){return uniform(rng);} double wrap(double x){return x-floor(x);} double mutate(double x,double s){double u=rnd(),delta=s*sqrt(-2*log(max(1e-12,u)))*cos(2*PI*rnd());return wrap(x+delta);}
struct Stream{const vector<double>& x;size_t i=0;double next(){return i<x.size()?x[i++]:.5;}};
V trace(Stream &s,int W,int H,int depth,int &pixel,double fov){double fx=s.next(),fy=s.next();int px=min(W-1,int(fx*W)),py=min(H-1,int(fy*H));pixel=py*W+px;double aspect=double(W)/H;double spread=3.35*tan(fov*PI/360.0);double sx=(2*fx-1)*aspect*spread,sy=(1-2*fy)*spread;V origin{0,1,-3.35},target{sx,1+sy,0};Ray ray{origin,norm(target-origin)};V throughput{1,1,1},radiance{};for(int bounce=0;bounce<depth;bounce++){Hit h=intersect(ray);if(!h.ok)break;if(lum(h.e)>0){radiance=radiance+throughput.mul(h.e);break;}V n=h.n;double r1=2*PI*s.next(),r2=s.next();double r=sqrt(r2);V helper=abs(n.x)>.5?V{0,1,0}:V{1,0,0};V tangent=norm(cross(helper,n)),bitangent=cross(n,tangent);V d=norm(tangent*(cos(r1)*r)+bitangent*(sin(r1)*r)+n*sqrt(1-r2));throughput=throughput.mul(h.c);ray={h.p+n*1e-4,d};}return radiance;}
void evaluate(Sample &s,int W,int H,int depth,double fov){Stream stream{s.x};s.value=trace(stream,W,H,depth,s.pixel,fov);s.weight=max(0.,lum(s.value));}
struct Preview {
 Display* d=nullptr; Window win=0; GC gc=0; XImage* xi=nullptr; int w=0,h=0;
 Preview(int W,int H,bool enabled):w(W),h(H){
  if(!enabled)return;
  d=XOpenDisplay(nullptr);
  if(!d){cerr<<"Preview unavailable (DISPLAY/X11 not found); rendering continues.\n";return;}
  int screen=DefaultScreen(d);
  win=XCreateSimpleWindow(d,RootWindow(d,screen),20,20,W,H,0,0,BlackPixel(d,screen));
  XStoreName(d,win,"Cornell MLT - live framebuffer");
  XSelectInput(d,win,ExposureMask|StructureNotifyMask);
  XMapWindow(d,win);gc=XCreateGC(d,win,0,nullptr);
  int depth=DefaultDepth(d,screen);
  xi=XCreateImage(d,DefaultVisual(d,screen),depth,ZPixmap,0,nullptr,W,H,32,0);
  if(!xi){cerr<<"Could not create XImage\n";XFreeGC(d,gc);XDestroyWindow(d,win);XCloseDisplay(d);d=nullptr;return;}
  xi->data=(char*)calloc(size_t(xi->bytes_per_line)*H,1);
  if(!xi->data){XDestroyImage(xi);xi=nullptr;XFreeGC(d,gc);XDestroyWindow(d,win);XCloseDisplay(d);d=nullptr;}
 }
 bool active()const{return d!=nullptr;}
 static unsigned long channel(unsigned char v,unsigned long mask){if(!mask)return 0;int shift=0;while(((mask>>shift)&1UL)==0)++shift;unsigned long m=mask>>shift;return ((static_cast<unsigned long>(v)*m+127)/255)<<shift;}
 void update(const vector<V>& image,double scale){
  if(!active())return;
  while(XPending(d)){XEvent ev;XNextEvent(d,&ev);if(ev.type==DestroyNotify){d=nullptr;return;}}
  for(int y=0;y<h;y++)for(int x=0;x<w;x++){
   V v=image[size_t(y)*w+x]*scale;
   auto tone=[](double a)->unsigned char{a=max(0.,a);return (unsigned char)clamp(int(pow(a/(1+a),1/2.2)*255+.5),0,255);};
   unsigned long pixel=channel(tone(v.x),xi->red_mask)|channel(tone(v.y),xi->green_mask)|channel(tone(v.z),xi->blue_mask);
   XPutPixel(xi,x,y,pixel);
  }
  XPutImage(d,win,gc,xi,0,0,0,0,w,h);XFlush(d);
 }
 ~Preview(){if(!d)return;if(xi)XDestroyImage(xi);XFreeGC(d,gc);XDestroyWindow(d,win);XCloseDisplay(d);}
};
int main(int argc,char**argv){int W=argc>1?stoi(argv[1]):512,H=argc>2?stoi(argv[2]):512;long long mutations=argc>3?stoll(argv[3]):2000000;int depth=argc>4?stoi(argv[4]):8;uint64_t seed=argc>5?stoull(argv[5]):42;double fov=argc>6?stod(argv[6]):55.0;bool preview=argc>7?stoi(argv[7])!=0:true;if(W<=0||H<=0||mutations<=0||depth<=0||depth>64||fov<=1||fov>=170){cerr<<"Invalid arguments\n";return 1;}rng.seed(seed);setup();const int dimensions=2+2*depth;auto randomSample=[&](){Sample s;s.x.resize(dimensions);for(double &x:s.x)x=rnd();evaluate(s,W,H,depth,fov);return s;};
// Independent uniform samples estimate integral b and provide a nonzero initial state.
int bootstrap=100000;double b=0;Sample current;for(int i=0;i<bootstrap;i++){Sample s=randomSample();b+=s.weight;if(s.weight>0&&(current.weight==0||rnd()<s.weight/(current.weight+s.weight)))current=move(s);}b/=bootstrap;if(b<=0){cerr<<"No light paths found; increase bootstrap or depth\n";return 1;}
// Reinitialize with a uniform sample chosen proportional to its contribution (reservoir sampling).
double total=0;for(int i=0;i<bootstrap;i++){Sample s=randomSample();total+=s.weight;if(s.weight>0&&rnd()*total<s.weight)current=move(s);}
vector<V> image(size_t(W)*H);Preview framebuffer(W,H,preview);const double pLarge=.30;auto splat=[&](const Sample&s,double factor){if(s.weight>0)image[s.pixel]=image[s.pixel]+s.value*(factor/s.weight);};auto start=chrono::steady_clock::now();auto lastRefresh=start;for(long long i=0;i<mutations;i++){bool large=rnd()<pLarge;Sample candidate=current;if(large){for(double &x:candidate.x)x=rnd();}else{for(double &x:candidate.x)x=mutate(x,.012);}evaluate(candidate,W,H,depth,fov);double a=current.weight>0?min(1.,candidate.weight/current.weight):1.;splat(candidate,a);splat(current,1-a);if(rnd()<a)current=move(candidate);auto now=chrono::steady_clock::now();if(framebuffer.active()&&(chrono::duration<double>(now-lastRefresh).count()>=0.5||i+1==mutations)){
 framebuffer.update(image,b*W*H/double(i+1));lastRefresh=now;
 }if((i+1)%max(1LL,mutations/20)==0){double secs=chrono::duration<double>(chrono::steady_clock::now()-start).count();cerr<<"\r"<<setw(3)<<100*(i+1)/mutations<<"% | "<<fixed<<setprecision(0)<<(i+1)/max(.001,secs)<<" mutations/s"<<flush;}}
// Metropolis estimator: b/N times stationary-chain contributions.
double scale=b*W*H/mutations;ofstream out("cornell.ppm",ios::binary);out<<"P6\n"<<W<<" "<<H<<"\n255\n";for(V v:image){v=v*scale;auto toByte=[](double x){return (unsigned char)clamp(int(pow(max(0.,x)/(1+max(0.,x)),1/2.2)*255+.5),0,255);};unsigned char rgb[3]={toByte(v.x),toByte(v.y),toByte(v.z)};out.write((char*)rgb,3);}cerr<<"\nSaved cornell.ppm; bootstrap mean="<<b<<"\n";
}
