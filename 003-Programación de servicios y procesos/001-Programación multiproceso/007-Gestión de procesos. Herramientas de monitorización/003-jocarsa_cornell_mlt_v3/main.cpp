// Cornell Box - Primary Sample Space Metropolis Light Transport (PSSMLT)
// Single-threaded C++17 renderer, configurable 3D camera and X11 framebuffer.
#include <X11/Xlib.h>
#include <X11/Xutil.h>
#include <algorithm>
#include <chrono>
#include <cmath>
#include <cstdint>
#include <cstdlib>
#include <fstream>
#include <iomanip>
#include <iostream>
#include <limits>
#include <random>
#include <stdexcept>
#include <string>
#include <vector>

constexpr double PI = 3.14159265358979323846;
constexpr double EPS = 1e-5;
struct Vec3 {
    double x=0,y=0,z=0;
    Vec3 operator+(Vec3 b) const {return {x+b.x,y+b.y,z+b.z};}
    Vec3 operator-(Vec3 b) const {return {x-b.x,y-b.y,z-b.z};}
    Vec3 operator-() const {return {-x,-y,-z};}
    Vec3 operator*(double a) const {return {x*a,y*a,z*a};}
    Vec3 operator/(double a) const {return *this*(1.0/a);}
    Vec3 multiply(Vec3 b) const {return {x*b.x,y*b.y,z*b.z};}
};
static double dot(Vec3 a,Vec3 b){return a.x*b.x+a.y*b.y+a.z*b.z;}
static Vec3 cross(Vec3 a,Vec3 b){return {a.y*b.z-a.z*b.y,a.z*b.x-a.x*b.z,a.x*b.y-a.y*b.x};}
static Vec3 normalize(Vec3 a){double d=std::sqrt(dot(a,a));return d>1e-15?a/d:Vec3{};}
static double luminance(Vec3 a){return .2126*a.x+.7152*a.y+.0722*a.z;}
static double clamp01(double x){return std::clamp(x,0.0,1.0);}
struct Ray {Vec3 origin,direction;};
struct Quad {Vec3 corner,edgeU,edgeV,albedo,emission;};
struct Hit {double distance=1e30;Vec3 point,normal,albedo,emission;bool found=false;};

class Scene {
    std::vector<Quad> quads;
public:
    void addQuad(Vec3 p,Vec3 u,Vec3 v,Vec3 color,Vec3 emission={}){
        quads.push_back({p,u,v,color,emission});
    }
    void addBox(Vec3 lo,Vec3 hi,Vec3 color){
        Vec3 u{hi.x-lo.x,0,0},v{0,hi.y-lo.y,0},w{0,0,hi.z-lo.z};
        addQuad(lo,u,v,color);addQuad(lo+w,u,v,color);
        addQuad(lo,u,w,color);addQuad(lo+v,u,w,color);
        addQuad(lo,v,w,color);addQuad(lo+u,v,w,color);
    }
    static Scene cornell(){
        Scene s;Vec3 white{.73,.73,.73};
        // Coordinates: X left/right, Y height, Z depth. Front opening: Z=0.
        s.addQuad({-1,0,0},{0,2,0},{0,0,2},{.75,.12,.12});
        s.addQuad({1,0,0},{0,0,2},{0,2,0},{.12,.70,.15});
        s.addQuad({-1,0,2},{2,0,0},{0,2,0},white);
        s.addQuad({-1,0,0},{2,0,0},{0,0,2},white);
        s.addQuad({-1,2,0},{0,0,2},{2,0,0},white);
        s.addQuad({-.34,1.999,.68},{.68,0,0},{0,0,.65},{},{16,15,13});
        s.addBox({-.76,0,.88},{-.13,.67,1.54},{.76,.76,.76});
        s.addBox({.18,0,.45},{.76,1.22,1.10},{.76,.76,.76});
        return s;
    }
    Hit intersect(const Ray& ray)const{
        Hit result;
        for(const Quad& q:quads){
            Vec3 n=normalize(cross(q.edgeU,q.edgeV));
            double den=dot(n,ray.direction);
            if(std::abs(den)<1e-10)continue;
            double t=dot(q.corner-ray.origin,n)/den;
            if(t<EPS||t>=result.distance)continue;
            Vec3 delta=ray.origin+ray.direction*t-q.corner;
            // Gram matrix inverse: supports even non-orthogonal quads.
            double a=dot(q.edgeU,q.edgeU),b=dot(q.edgeU,q.edgeV),c=dot(q.edgeV,q.edgeV);
            double determinant=a*c-b*b;
            if(determinant<1e-15)continue;
            double du=dot(delta,q.edgeU),dv=dot(delta,q.edgeV);
            double u=(du*c-dv*b)/determinant,v=(dv*a-du*b)/determinant;
            if(u<0||u>1||v<0||v>1)continue;
            result={t,ray.origin+ray.direction*t,den<0?n:-n,q.albedo,q.emission,true};
        }
        return result;
    }
};

struct Camera {
    Vec3 position{0,1,-3.35};
    Vec3 target{0,1,1};
    Vec3 worldUp{0,1,0};
    double fovDegrees=65;
    Vec3 forward,right,up;
    void prepare(){
        forward=normalize(target-position);
        if(dot(forward,forward)<.9)throw std::runtime_error("Camera position and target must differ");
        right=normalize(cross(forward,worldUp));
        if(dot(right,right)<.9)throw std::runtime_error("Camera is parallel to world up");
        up=normalize(cross(right,forward));
    }
    Ray generate(double sx,double sy,int width,int height)const{
        double aspect=double(width)/height;
        double scale=std::tan(fovDegrees*PI/360.0);
        Vec3 direction=normalize(forward+right*((2*sx-1)*aspect*scale)+up*((1-2*sy)*scale));
        return {position,direction};
    }
};

class Random {
    std::mt19937_64 engine;
    std::uniform_real_distribution<double> uniform{0,1};
public:
    explicit Random(uint64_t seed):engine(seed){}
    double next(){return uniform(engine);}
    double normal(){double a=std::max(1e-15,next());return std::sqrt(-2*std::log(a))*std::cos(2*PI*next());}
};
struct PrimaryStream {
    const std::vector<double>& values;
    size_t index=0;
    double next(){return index<values.size()?values[index++]:.5;}
};
struct PathSample {
    std::vector<double> primary;
    Vec3 radiance;
    double importance=0;
    size_t pixel=0;
};
struct Settings {
    int width=512,height=512,depth=8,bootstrap=100000;
    long long mutations=10000000;
    uint64_t seed=42;
    double fov=65,smallStep=.012,largeProbability=.30,exposure=1;
    bool preview=true;
    std::string output="cornell.ppm";
    Camera camera;
};

class PathIntegrator {
    const Scene& scene;
    const Camera& camera;
    const Settings& settings;
public:
    PathIntegrator(const Scene& s,const Camera& c,const Settings& cfg):scene(s),camera(c),settings(cfg){}
    void evaluate(PathSample& sample)const{
        PrimaryStream stream{sample.primary};
        double fx=stream.next(),fy=stream.next();
        int px=std::min(settings.width-1,int(fx*settings.width));
        int py=std::min(settings.height-1,int(fy*settings.height));
        sample.pixel=size_t(py)*settings.width+px;
        Ray ray=camera.generate(fx,fy,settings.width,settings.height);
        Vec3 throughput{1,1,1},radiance{};
        for(int bounce=0;bounce<settings.depth;bounce++){
            Hit h=scene.intersect(ray);
            if(!h.found)break;
            if(luminance(h.emission)>0){radiance=radiance+throughput.multiply(h.emission);break;}
            double angle=2*PI*stream.next(),radius=std::sqrt(stream.next());
            Vec3 helper=std::abs(h.normal.x)>.5?Vec3{0,1,0}:Vec3{1,0,0};
            Vec3 tangent=normalize(cross(helper,h.normal));
            Vec3 bitangent=cross(h.normal,tangent);
            Vec3 direction=normalize(tangent*(std::cos(angle)*radius)+bitangent*(std::sin(angle)*radius)+h.normal*std::sqrt(std::max(0.0,1-radius*radius)));
            throughput=throughput.multiply(h.albedo);
            ray={h.point+h.normal*1e-4,direction};
        }
        sample.radiance=radiance;
        sample.importance=std::max(0.0,luminance(radiance));
    }
};

static unsigned char toByte(double v){
    v=std::max(0.0,v);v=v/(1+v);
    return (unsigned char)std::clamp(int(std::pow(v,1.0/2.2)*255+.5),0,255);
}
class Framebuffer {
    int width,height;
    std::vector<Vec3> accum;
public:
    Framebuffer(int w,int h):width(w),height(h),accum(size_t(w)*h){}
    void splat(const PathSample& sample,double weight){
        if(sample.importance>0)accum[sample.pixel]=accum[sample.pixel]+sample.radiance*(weight/sample.importance);
    }
    std::vector<unsigned char> pixels(double normalization,double exposure)const{
        std::vector<unsigned char> rgb(accum.size()*3);
        for(size_t i=0;i<accum.size();i++){
            Vec3 c=accum[i]*(normalization*exposure);
            rgb[i*3]=toByte(c.x);rgb[i*3+1]=toByte(c.y);rgb[i*3+2]=toByte(c.z);
        }
        return rgb;
    }
    void save(const std::string& path,double normalization,double exposure)const{
        std::ofstream file(path,std::ios::binary);
        if(!file)throw std::runtime_error("Cannot write output: "+path);
        file<<"P6\n"<<width<<" "<<height<<"\n255\n";
        auto bytes=pixels(normalization,exposure);
        file.write(reinterpret_cast<const char*>(bytes.data()),bytes.size());
    }
};

class X11Preview {
    Display* display=nullptr;
    Window window=0;
    GC gc=0;
    XImage* image=nullptr;
    int width,height;
    bool closed=false;
    static unsigned long channel(unsigned char value,unsigned long mask){
        if(!mask)return 0;
        int shift=0;while(((mask>>shift)&1UL)==0)++shift;
        unsigned long maxValue=mask>>shift;
        return ((static_cast<unsigned long>(value)*maxValue+127)/255)<<shift;
    }
public:
    X11Preview(int w,int h,bool enabled):width(w),height(h){
        if(!enabled)return;
        display=XOpenDisplay(nullptr);
        if(!display){std::cerr<<"X11 unavailable; rendering headlessly.\n";return;}
        int screen=DefaultScreen(display);
        window=XCreateSimpleWindow(display,RootWindow(display,screen),30,30,w,h,0,0,BlackPixel(display,screen));
        XStoreName(display,window,"JOCARSA | Cornell PSSMLT - Live framebuffer");
        XSelectInput(display,window,ExposureMask|StructureNotifyMask);
        Atom closeAtom=XInternAtom(display,"WM_DELETE_WINDOW",False);
        XSetWMProtocols(display,window,&closeAtom,1);
        XMapWindow(display,window);
        gc=XCreateGC(display,window,0,nullptr);
        image=XCreateImage(display,DefaultVisual(display,screen),DefaultDepth(display,screen),ZPixmap,0,nullptr,w,h,32,0);
        if(!image)throw std::runtime_error("XCreateImage failed");
        image->data=(char*)std::calloc(size_t(image->bytes_per_line)*h,1);
        if(!image->data)throw std::bad_alloc();
    }
    bool active()const{return display&&!closed;}
    void update(const std::vector<unsigned char>& rgb){
        if(!active())return;
        while(XPending(display)){
            XEvent event;XNextEvent(display,&event);
            if(event.type==ClientMessage||event.type==DestroyNotify){closed=true;return;}
        }
        for(int y=0;y<height;y++)for(int x=0;x<width;x++){
            size_t i=(size_t(y)*width+x)*3;
            unsigned long pixel=channel(rgb[i],image->red_mask)|channel(rgb[i+1],image->green_mask)|channel(rgb[i+2],image->blue_mask);
            XPutPixel(image,x,y,pixel);
        }
        XPutImage(display,window,gc,image,0,0,0,0,width,height);
        XFlush(display);
    }
    ~X11Preview(){
        if(image)XDestroyImage(image);
        if(display){XFreeGC(display,gc);XDestroyWindow(display,window);XCloseDisplay(display);}
    }
};

static void help(){
    std::cout<<R"(Cornell Box PSSMLT - C++17 single-core renderer
Usage: ./cornell_mlt [options]

  --width N              Image width (default 512)
  --height N             Image height (default 512)
  --mutations N          MLT mutations (default 10000000)
  --depth N              Maximum bounces (default 8)
  --bootstrap N          Independent bootstrap samples (default 100000)
  --seed N               Random seed (default 42)
  --fov DEGREES          Vertical camera field of view (default 65)
  --camera-x V           Camera X (left/right, default 0)
  --camera-y V           Camera Y (height, default 1)
  --camera-z V           Camera Z (depth, default -3.35)
  --target-x V           Look-at X (default 0)
  --target-y V           Look-at Y (default 1)
  --target-z V           Look-at Z (default 1)
  --preview 0|1          X11 live preview (default 1)
  --small-step V         Primary-space mutation stddev (default .012)
  --large-prob V         Probability of independent proposal (default .30)
  --exposure V           Exposure multiplier (default 1)
  --output PATH          PPM output path (default cornell.ppm)
  --help                 Show this help

Coordinate convention: X right, Y up, Z into the box.
Example: ./cornell_mlt --camera-x 0.3 --camera-y 1.2 --camera-z -4 \\
    --target-x 0 --target-y 1 --target-z 1 --fov 75
)";
}
static Settings parse(int argc,char**argv){
    Settings s;
    for(int i=1;i<argc;i++){
        std::string key=argv[i];
        if(key=="--help"||key=="-h"){help();std::exit(0);}
        if(i+1>=argc)throw std::runtime_error("Missing value for "+key);
        std::string value=argv[++i];
        if(key=="--width")s.width=std::stoi(value);
        else if(key=="--height")s.height=std::stoi(value);
        else if(key=="--mutations")s.mutations=std::stoll(value);
        else if(key=="--depth")s.depth=std::stoi(value);
        else if(key=="--bootstrap")s.bootstrap=std::stoi(value);
        else if(key=="--seed")s.seed=std::stoull(value);
        else if(key=="--fov")s.fov=std::stod(value);
        else if(key=="--camera-x")s.camera.position.x=std::stod(value);
        else if(key=="--camera-y")s.camera.position.y=std::stod(value);
        else if(key=="--camera-z")s.camera.position.z=std::stod(value);
        else if(key=="--target-x")s.camera.target.x=std::stod(value);
        else if(key=="--target-y")s.camera.target.y=std::stod(value);
        else if(key=="--target-z")s.camera.target.z=std::stod(value);
        else if(key=="--preview")s.preview=std::stoi(value)!=0;
        else if(key=="--small-step")s.smallStep=std::stod(value);
        else if(key=="--large-prob")s.largeProbability=std::stod(value);
        else if(key=="--exposure")s.exposure=std::stod(value);
        else if(key=="--output")s.output=value;
        else throw std::runtime_error("Unknown option: "+key);
    }
    if(s.width<1||s.height<1||s.width>16384||s.height>16384||s.mutations<1||s.depth<1||s.depth>64||s.bootstrap<1||s.fov<=1||s.fov>=170||s.smallStep<=0||s.largeProbability<0||s.largeProbability>1||s.exposure<=0)
        throw std::runtime_error("Invalid parameter range; see --help");
    s.camera.fovDegrees=s.fov;s.camera.prepare();
    return s;
}

class Renderer {
    Settings settings;
    Scene scene;
    Random random;
    PathIntegrator integrator;
    Framebuffer framebuffer;
    X11Preview preview;
    int dimensions;
    PathSample independent(){
        PathSample s;s.primary.resize(dimensions);
        for(double& x:s.primary)x=random.next();
        integrator.evaluate(s);return s;
    }
    static double wrap(double x){return x-std::floor(x);}
    PathSample proposal(const PathSample& current,bool large){
        PathSample s=current;
        for(double& x:s.primary)x=large?random.next():wrap(x+settings.smallStep*random.normal());
        integrator.evaluate(s);return s;
    }
public:
    explicit Renderer(Settings cfg):settings(cfg),scene(Scene::cornell()),random(cfg.seed),integrator(scene,settings.camera,settings),framebuffer(cfg.width,cfg.height),preview(cfg.width,cfg.height,cfg.preview),dimensions(2+2*cfg.depth){}
    void run(){
        // Bootstrap: estimate average importance and select a state with weighted reservoir sampling.
        double sum=0;PathSample current;
        for(int i=0;i<settings.bootstrap;i++){
            PathSample s=independent();sum+=s.importance;
            if(s.importance>0&&random.next()*sum<s.importance)current=std::move(s);
        }
        double b=sum/settings.bootstrap;
        if(b<=0)throw std::runtime_error("Bootstrap found no paths hitting light; increase --bootstrap or --depth");
        std::cout<<"Camera: ("<<settings.camera.position.x<<", "<<settings.camera.position.y<<", "<<settings.camera.position.z<<")"
                 <<" -> ("<<settings.camera.target.x<<", "<<settings.camera.target.y<<", "<<settings.camera.target.z<<") | FOV "<<settings.fov<<"\n";
        std::cout<<"Bootstrap importance: "<<b<<" | sequential CPU render\n";
        auto start=std::chrono::steady_clock::now(),last=start;
        long long accepted=0;
        for(long long i=0;i<settings.mutations;i++){
            PathSample candidate=proposal(current,random.next()<settings.largeProbability);
            double acceptance=current.importance>0?std::min(1.0,candidate.importance/current.importance):1.0;
            framebuffer.splat(candidate,acceptance);
            framebuffer.splat(current,1-acceptance);
            if(random.next()<acceptance){current=std::move(candidate);accepted++;}
            auto now=std::chrono::steady_clock::now();
            if(std::chrono::duration<double>(now-last).count()>=.5||i+1==settings.mutations){
                double normalization=b*settings.width*settings.height/double(i+1);
                if(preview.active())preview.update(framebuffer.pixels(normalization,settings.exposure));
                double elapsed=std::chrono::duration<double>(now-start).count();
                std::cerr<<"\r"<<std::fixed<<std::setprecision(1)<<100.0*(i+1)/settings.mutations
                         <<"% | "<<std::setprecision(0)<<(i+1)/std::max(.001,elapsed)
                         <<" mutations/s | accepted "<<std::setprecision(1)<<100.0*accepted/(i+1)<<"%   "<<std::flush;
                last=now;
            }
        }
        framebuffer.save(settings.output,b*settings.width*settings.height/double(settings.mutations),settings.exposure);
        std::cerr<<"\nSaved: "<<settings.output<<"\n";
    }
};
int main(int argc,char**argv){
    try{Settings cfg=parse(argc,argv);Renderer renderer(cfg);renderer.run();return 0;}
    catch(const std::exception& e){std::cerr<<"Error: "<<e.what()<<"\n";return 1;}
}
