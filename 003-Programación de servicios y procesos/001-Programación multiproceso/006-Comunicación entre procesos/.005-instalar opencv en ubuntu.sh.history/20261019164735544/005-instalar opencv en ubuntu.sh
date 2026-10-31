sudo apt update
sudo apt install libopencv-dev

compilar con: 
g++ "006-leer color.cpp"  $(pkg-config --cflags --libs opencv4)
