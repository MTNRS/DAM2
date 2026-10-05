Prerrequisitos:

sudo apt update

sudo apt install -y \
  python3 \
  python3-venv \
  python3-pip \
  ffmpeg
  
python3 -m pip install -U openai-whisper --break-system-packages

whisper video.mp4 \
  --model medium \
  --language Spanish \
  --output_format srt