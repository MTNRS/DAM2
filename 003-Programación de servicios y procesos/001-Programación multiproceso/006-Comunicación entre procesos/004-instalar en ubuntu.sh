sudo apt update
sudo apt install -y build-essential libgomp1

g++ -O3 -fopenmp programa.cpp -o programa