Prerrequisitos:

sudo apt update

sudo apt install -y \
  python3 \ 
  python3-venv \
  python3-pip \
  ffmpeg
  
python3 -m pip install -U openai-whisper --break-system-packages

whisper 002-utilizacion-de-formularios-desde-codigo-2026-10-05_13-44-07.webm \
  --model medium \
  --language Spanish \
  --output_format srt 
 
Formato para HTML5
whisper clase.webm \
  --model medium \
  --language Spanish \
  --device cuda \
  --output_format vtt 