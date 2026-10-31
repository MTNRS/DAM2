
#include <iostream>
#include <opencv2/opencv.hpp>

int main() {

    cv::Mat imagen = cv::imread("imagen.jpg");

    if (imagen.empty()) {
        std::cerr << "Error opening image" << std::endl;
        return 1;
    }

    std::cout << "Width: " << imagen.cols << std::endl;
    std::cout << "Height: " << imagen.rows << std::endl;

    for (int y = 0; y < imagen.rows; y++) {
        for (int x = 0; x < imagen.cols; x++) {

            cv::Vec3b pixel = imagen.at<cv::Vec3b>(y, x);

            int azul = pixel[0];
            int verde = pixel[1];
            int rojo = pixel[2];

            std::cout
                << "Pixel (" << x << "," << y << ") "
                << "R:" << rojo << " "
                << "G:" << verde << " "
                << "B:" << azul
                << std::endl;
        }
    }

    return 0;
}
