// Cornell Box - Primary Sample Space Metropolis Light Transport (PSSMLT)
// Single-threaded C++17 renderer, configurable 3D camera and X11 framebuffer.
#include <omp.h>
#include <X11/Xlib.h>
#include <X11/Xutil.h>
#include <X11/keysym.h>
#include <array>
#include <thread>
#include <algorithm>
#include <chrono>
#include <cmath>
#include <cstdint>
#include <cstdlib>
#include <fstream>
#include <iomanip>
#include <iostream>
#include <limits>
#include <memory>
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
    Vec3 position{0,1,-1};
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
    int width=512,height=512,depth=8,bootstrap=100000,threads=0;
    double moveSpeed=1.5,mouseSensitivity=.0025,persistence=.88;
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
    void merge(const Framebuffer& other){for(size_t i=0;i<accum.size();++i)accum[i]=accum[i]+other.accum[i];}
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
    bool closed=false, quit=false, grabbed=false;
    std::array<bool,256> held{};
    int lastMouseX=0,lastMouseY=0;
    double deltaX=0,deltaY=0;
    Atom wmDelete=0;
    std::vector<double> phosphor;
    double persistence=0.88;
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
        XSelectInput(display,window,ExposureMask|StructureNotifyMask|KeyPressMask|KeyReleaseMask|PointerMotionMask|ButtonPressMask);
        wmDelete=XInternAtom(display,"WM_DELETE_WINDOW",False);
        XSetWMProtocols(display,window,&wmDelete,1);
        XMapWindow(display,window);
        gc=XCreateGC(display,window,0,nullptr);
        image=XCreateImage(display,DefaultVisual(display,screen),DefaultDepth(display,screen),ZPixmap,0,nullptr,w,h,32,0);
        if(!image)throw std::runtime_error("XCreateImage failed");
        image->data=(char*)std::calloc(size_t(image->bytes_per_line)*h,1);
        if(!image->data)throw std::bad_alloc();
        phosphor.assign(size_t(w)*h*3,0.0);
    }
    bool active()const{return display&&!closed;}
    bool shouldQuit()const{return quit||closed;}
    bool key(KeySym sym)const{return sym>=0 && sym<256 && held[size_t(sym)];}
    void setPersistence(double p){persistence=std::clamp(p,0.0,0.999);}
    void poll(){
        if(!active())return;
        while(XPending(display)){
            XEvent event;XNextEvent(display,&event);
            if(event.type==ClientMessage && Atom(event.xclient.data.l[0])==wmDelete){quit=true;continue;}
            if(event.type==DestroyNotify){closed=true;quit=true;continue;}
            if(event.type==KeyPress||event.type==KeyRelease){
                KeySym sym=XLookupKeysym(&event.xkey,0);
                if(sym==XK_Escape && event.type==KeyPress){quit=true;continue;}
                if(sym>=0&&sym<256)held[size_t(sym)]=(event.type==KeyPress);
                continue;
            }
            if(event.type==ButtonPress && event.xbutton.button==Button1){
                grabbed=!grabbed;
                if(grabbed){
                    XGrabPointer(display,window,True,PointerMotionMask|ButtonPressMask|ButtonReleaseMask,
                                 GrabModeAsync,GrabModeAsync,window,None,CurrentTime);
                    XWarpPointer(display,None,window,0,0,0,0,width/2,height/2);
                    lastMouseX=width/2;lastMouseY=height/2;
                }else XUngrabPointer(display,CurrentTime);
            }
            if(event.type==MotionNotify && grabbed){
                // Ignore warp-generated motion events at the center.
                if(event.xmotion.x==width/2 && event.xmotion.y==height/2)continue;
                deltaX+=event.xmotion.x-width/2;
                deltaY+=event.xmotion.y-height/2;
                XWarpPointer(display,None,window,0,0,0,0,width/2,height/2);
            }
        }
    }
    std::pair<double,double> consumeMouse(){auto v=std::make_pair(deltaX,deltaY);deltaX=deltaY=0;return v;}
    void update(const std::vector<unsigned char>& rgb){
        if(!active())return;
        // The displayed image is a decaying phosphor buffer, not the unbiased MLT accumulator.
        // Blend in linear display RGB and let old frames fade toward black.
        for(size_t i=0;i<rgb.size();++i)
            phosphor[i]=std::min(255.0,phosphor[i]*persistence+double(rgb[i])*(1.0-persistence));
        for(int y=0;y<height;y++)for(int x=0;x<width;x++){
            size_t i=(size_t(y)*width+x)*3;
            unsigned long pixel=channel((unsigned char)phosphor[i],image->red_mask)
                |channel((unsigned char)phosphor[i+1],image->green_mask)
                |channel((unsigned char)phosphor[i+2],image->blue_mask);
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
    std::cout<<R"(Cornell Box PSSMLT - C++17 OpenMP multicore renderer
Usage: ./cornell_mlt [options]

  --threads N            OpenMP worker threads (default: all available)
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

X11: click to capture/release mouse, WASD to move, mouse to look, ESC to exit.
Q/E move down/up. Moving the camera restarts MLT sampling; phosphor fades.
)";
}
static Settings parse(int argc,char**argv){
    Settings s;
    for(int i=1;i<argc;i++){
        std::string key=argv[i];
        if(key=="--help"||key=="-h"){help();std::exit(0);}
        if(i+1>=argc)throw std::runtime_error("Missing value for "+key);
        std::string value=argv[++i];
        if(key=="--threads")s.threads=std::stoi(value);
        else if(key=="--width")s.width=std::stoi(value);
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
        else if(key=="--move-speed")s.moveSpeed=std::stod(value);
        else if(key=="--mouse-sensitivity")s.mouseSensitivity=std::stod(value);
        else if(key=="--persistence")s.persistence=std::stod(value);
        else if(key=="--small-step")s.smallStep=std::stod(value);
        else if(key=="--large-prob")s.largeProbability=std::stod(value);
        else if(key=="--exposure")s.exposure=std::stod(value);
        else if(key=="--output")s.output=value;
        else throw std::runtime_error("Unknown option: "+key);
    }
    if(s.threads<0||s.width<1||s.height<1||s.width>16384||s.height>16384||s.mutations<1||s.depth<1||s.depth>64||s.bootstrap<1||s.fov<=1||s.fov>=170||s.smallStep<=0||s.largeProbability<0||s.largeProbability>1||s.exposure<=0||s.moveSpeed<=0||s.mouseSensitivity<=0||s.persistence<0||s.persistence>=1)
        throw std::runtime_error("Invalid parameter range; see --help");
    s.camera.fovDegrees=s.fov;s.camera.prepare();
    return s;
}

// Independent Markov chains, one per OpenMP worker. Each worker owns its RNG,
// chain state and accumulation buffer. The X11 window belongs to the main thread.
class Renderer {
    Settings settings;
    Scene scene;
    PathIntegrator integrator;
    Framebuffer framebuffer;
    X11Preview preview;
    int dimensions;
    struct Worker {
        Random random;
        Framebuffer buffer;
        PathSample current;
        long long accepted=0, done=0;
        Worker(uint64_t seed,int w,int h):random(seed),buffer(w,h){}
    };
    static uint64_t mix(uint64_t x){
        x+=0x9e3779b97f4a7c15ULL;
        x=(x^(x>>30))*0xbf58476d1ce4e5b9ULL;
        x=(x^(x>>27))*0x94d049bb133111ebULL;
        return x^(x>>31);
    }
    PathSample independent(Random& rng)const{
        PathSample s;s.primary.resize(dimensions);
        for(double& x:s.primary)x=rng.next();
        integrator.evaluate(s);return s;
    }
    PathSample proposal(Random& rng,const PathSample& current,bool large)const{
        PathSample s=current;
        for(double& x:s.primary){
            double v=large?rng.next():x+settings.smallStep*rng.normal();
            x=v-std::floor(v);
        }
        integrator.evaluate(s);return s;
    }
public:
    explicit Renderer(Settings cfg):settings(cfg),scene(Scene::cornell()),
        integrator(scene,settings.camera,settings),framebuffer(cfg.width,cfg.height),
        preview(cfg.width,cfg.height,cfg.preview),dimensions(2+2*cfg.depth){preview.setPersistence(cfg.persistence);}
    void run(){
        if(settings.threads>0)omp_set_num_threads(settings.threads);
        int n=1;
        #pragma omp parallel
        {
            #pragma omp single
            n=omp_get_num_threads();
        }
        std::vector<std::unique_ptr<Worker>> workers;
        for(int t=0;t<n;++t)
            workers.emplace_back(std::make_unique<Worker>(mix(settings.seed+uint64_t(t)),settings.width,settings.height));

        // Camera yaw/pitch derived from the initial look-at direction.
        Vec3 f=settings.camera.forward;
        double yaw=std::atan2(f.x,f.z),pitch=std::asin(std::clamp(f.y,-1.0,1.0));
        auto lastTick=std::chrono::steady_clock::now();
        auto lastDisplay=lastTick;
        auto start=lastTick;
        bool dirty=true,initialized=false;
        long long completed=0,accepted=0;
        double b=1.0;
        std::vector<unsigned char> black(size_t(settings.width)*settings.height*3,0);
        std::vector<unsigned char> lastRGB=black;
        std::cout<<"OpenMP workers: "<<n<<" | WASD move, mouse look (click to capture), Q/E vertical, ESC exit\n";

        auto initialize=[&](){
            // Independent bootstrap with a fresh reservoir for EACH camera position.
            // Markov chains are not allowed to retain paths from a different camera.
            std::vector<double> sums(n,0);
            #pragma omp parallel for schedule(static)
            for(int t=0;t<n;++t){
                auto& w=*workers[t];
                w.current=PathSample{};
                w.buffer=Framebuffer(settings.width,settings.height);
                w.accepted=w.done=0;
                long long begin=(int64_t(settings.bootstrap)*t)/n;
                long long finish=(int64_t(settings.bootstrap)*(t+1))/n;
                double sum=0;
                for(long long i=begin;i<finish;++i){
                    PathSample sample=independent(w.random);
                    sum+=sample.importance;
                    if(sample.importance>0 && w.random.next()*sum<sample.importance)
                        w.current=std::move(sample);
                }
                sums[t]=sum;
            }
            double total=0;
            for(double x:sums)total+=x;
            b=total/settings.bootstrap;
            if(b<=0)throw std::runtime_error("Bootstrap found no light paths; increase --bootstrap or --depth");
            const PathSample* fallback=nullptr;
            for(auto& w:workers)if(w->current.importance>0){fallback=&w->current;break;}
            if(!fallback)throw std::runtime_error("No valid bootstrap chain");
            for(auto& w:workers)if(w->current.importance<=0)w->current=*fallback;
            completed=accepted=0;
            initialized=true;
            dirty=false;
            start=std::chrono::steady_clock::now();
        };

        while(!preview.shouldQuit()){
            preview.poll();
            if(preview.shouldQuit())break;
            auto now=std::chrono::steady_clock::now();
            double dt=std::min(.1,std::chrono::duration<double>(now-lastTick).count());
            lastTick=now;
            auto mouse=preview.consumeMouse();
            bool moved=false;
            if(mouse.first!=0||mouse.second!=0){
                yaw+=mouse.first*settings.mouseSensitivity;
                pitch=std::clamp(pitch-mouse.second*settings.mouseSensitivity,-1.54,1.54);
                moved=true;
            }
            Vec3 forward{std::sin(yaw)*std::cos(pitch),std::sin(pitch),std::cos(yaw)*std::cos(pitch)};
            Vec3 right=normalize(cross(forward,{0,1,0}));
            Vec3 planar=normalize(Vec3{forward.x,0,forward.z});
            Vec3 move{};
            if(preview.key(XK_w)||preview.key(XK_W))move=move+planar;
            if(preview.key(XK_s)||preview.key(XK_S))move=move-planar;
            if(preview.key(XK_a)||preview.key(XK_A))move=move-right;
            if(preview.key(XK_d)||preview.key(XK_D))move=move+right;
            if(preview.key(XK_e)||preview.key(XK_E))move=move+Vec3{0,1,0};
            if(preview.key(XK_q)||preview.key(XK_Q))move=move-Vec3{0,1,0};
            if(dot(move,move)>0){
                settings.camera.position=settings.camera.position+normalize(move)*(settings.moveSpeed*dt);
                moved=true;
            }
            if(moved){
                settings.camera.target=settings.camera.position+forward;
                settings.camera.prepare();
                dirty=true;
                initialized=false;
                // Keep the previous displayed frame only as a fading afterimage.
                lastRGB=black;
            }
            // While the user is moving, do not waste time bootstrapping obsolete camera views.
            if(dirty && !moved)initialize();
            if(initialized && completed<settings.mutations){
                const long long chunk=1024;
                long long batch=std::min(settings.mutations-completed,chunk*int64_t(n));
                #pragma omp parallel for schedule(static)
                for(int t=0;t<n;++t){
                    auto& w=*workers[t];
                    long long begin=batch*t/n,finish=batch*(t+1)/n;
                    for(long long i=begin;i<finish;++i){
                        PathSample candidate=proposal(w.random,w.current,w.random.next()<settings.largeProbability);
                        double a=w.current.importance>0?std::min(1.0,candidate.importance/w.current.importance):1.0;
                        w.buffer.splat(candidate,a);
                        w.buffer.splat(w.current,1-a);
                        if(w.random.next()<a){w.current=std::move(candidate);++w.accepted;}
                        ++w.done;
                    }
                }
                completed+=batch;
            }
            now=std::chrono::steady_clock::now();
            if(std::chrono::duration<double>(now-lastDisplay).count()>=.05){
                if(initialized && completed>0){
                    Framebuffer snapshot(settings.width,settings.height);
                    accepted=0;
                    for(const auto& w:workers){snapshot.merge(w->buffer);accepted+=w->accepted;}
                    lastRGB=snapshot.pixels(b*settings.width*settings.height/double(completed),settings.exposure);
                }
                if(preview.active())preview.update(lastRGB);
                if(initialized && completed>0){
                    double elapsed=std::chrono::duration<double>(now-start).count();
                    std::cerr<<"\r"<<std::fixed<<std::setprecision(1)<<100.0*completed/settings.mutations
                        <<"% | "<<std::setprecision(0)<<completed/std::max(.001,elapsed)
                        <<" mutations/s | accepted "<<std::setprecision(1)<<100.0*accepted/completed
                        <<"% | "<<n<<" threads | camera ("<<std::setprecision(2)
                        <<settings.camera.position.x<<","<<settings.camera.position.y<<","<<settings.camera.position.z<<")    "<<std::flush;
                }
                lastDisplay=now;
            }
            if(!preview.active() && initialized && completed>=settings.mutations)break;
            // Keep the X11 window alive after convergence until ESC or close.
            if(preview.active() && initialized && completed>=settings.mutations)
                std::this_thread::sleep_for(std::chrono::milliseconds(10));
        }
        // Export only the latest camera's unbiased accumulation, never the ghosted display.
        if(initialized && completed>0){
            Framebuffer result(settings.width,settings.height);
            for(const auto& w:workers)result.merge(w->buffer);
            result.save(settings.output,b*settings.width*settings.height/double(completed),settings.exposure);
            std::cerr<<"\nSaved: "<<settings.output<<" ("<<completed<<" mutations for latest view)\n";
        }else std::cerr<<"\nNo completed samples for final camera view.\n";
    }

};
int main(int argc,char**argv){
    try{Settings cfg=parse(argc,argv);Renderer renderer(cfg);renderer.run();return 0;}
    catch(const std::exception& e){std::cerr<<"Error: "<<e.what()<<"\n";return 1;}
}
