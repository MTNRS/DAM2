Prueba sencilla:

whisper letyougo.mp3 \
  --model medium \
  --language en \
  --device cuda \
  --output_format vtt
  
Prueba profunda:
whisper letyougo.mp3 \
  --model large-v3 \
  --language en \
  --device cuda \
  --output_format vtt