Sí. Y conviene analizarlo **no como “qué avatar se ve más realista”**, sino como un sistema completo:

**usuario → percepción → IA → voz → comportamiento → animación → renderizado → usuario**

A septiembre de 2026, el estado del arte ya ha avanzado bastante más allá del clásico *“LLM + TTS + boca que se mueve”*. Los sistemas punteros intentan construir una **presencia digital multimodal en tiempo real**: ven, escuchan, conversan, recuerdan, expresan emociones y coordinan cara, mirada y cuerpo.

![Image](https://images.openai.com/static-rsc-4/iAx-YbemFsxFawGmUnFldTLGsGzqK3R-Htn5rveZGWuijjl9w1AcnsVikIWp_ZqNO7_jNLTcJjKdvf18yewEl-wUbCfdZUvUjGSBGj6VD2uhr_oa2ypFosESo6acUr4C7mVF8UXmZ61cZUCNSKCDf9mSUaFSJKtgGY4y1_i_33CJLNX-3JutxXz6SEe0GFt5?purpose=fullsize)

![Image](https://images.openai.com/static-rsc-4/urqN7DMqPvkhbY2rSF7A6kljuOhn-PtSlBbyIFCk9sV4J7lOca6UsMm_AnFTKQI30zr4ThKwqTPF4WlK948jvlknpEIfe1r2CieDhMFvM3eH6A69nByXtkBbITbvYxOA0CpumJxW9VfHb1UvI5XScrA9mgjc6VRhnuYm51BPiuM1iNfubcrKIoxQ8W3Ey8nM?purpose=fullsize)

![Image](https://images.openai.com/static-rsc-4/LMT_nB6QPIPQUeNGqSF8VKySdY3uAW75rdv-ub-AcBxagM7f1Oc2jWvvhP6avS8uJJKLlZdu8xnykpL1P25KR4hyxIHIHKO8DOafF_0FDVIAkSKMt6YCcdEN99LRkjkVRLFKU0BT2GVy-jQXOIrljvjBtf4YZaVPF6Y4B6lKEBos-bKB61wJDYSJE1XvgSa8?purpose=fullsize)

![Image](https://images.openai.com/static-rsc-4/PthZgL5My3-iPj3mAjp22-Psa3hEr3_UqpghiKnYYa3k_cqevt6MLx9MabfQ_IRfqSjvTbzIQ6z3nP3yIRuY5k2pAUjE6JTe5wwzR6iht0Bn_dGOs829PGiD3Mw5mXFweTNJTEFExYS8W25Fhp2TxUN5hH3KE-eaft552zl3AF_SzFba-zOqFI3t8uJIEJme?purpose=fullsize)

### Una taxonomía útil

Yo dividiría el estado del arte en estos niveles:

| Nivel | Tecnología                                  | Estado actual             |
| ----- | ------------------------------------------- | ------------------------- |
| 1     | Imagen + boca                               | superado                  |
| 2     | Lip-sync desde audio                        | commodity                 |
| 3     | Cara expresiva desde audio                  | muy maduro                |
| 4     | Cabeza + mirada + microgestos               | maduro                    |
| 5     | Cuerpo sincronizado con conversación        | activo desarrollo         |
| 6     | Avatar que percibe al interlocutor          | disponible comercialmente |
| 7     | Avatar con memoria + personalidad           | disponible                |
| 8     | Avatar agente que realiza acciones          | emergente / producción    |
| 9     | Humano digital prácticamente indistinguible | frontera de investigación |

La diferencia importante está entre **animar un personaje** y **modelar comportamiento humano**.

---

## 1. Cara: el problema está prácticamente resuelto

Aquí ha habido un salto enorme.

Un ejemplo especialmente interesante para lo que estás experimentando con GLB y *shape keys* es [NVIDIA Audio2Face](https://developer.nvidia.com/audio2face?utm_source=chatgpt.com).

Audio2Face convierte directamente:

**audio → fonemas/prosodia/emoción → parámetros faciales**

y puede producir *blendshapes* compatibles con ARKit. NVIDIA además abrió Audio2Face en 2025, incluyendo SDK, modelos y framework de entrenamiento. ([NVIDIA Docs][1])

Esto es conceptualmente muy cercano a lo que estás haciendo ahora:

```text
avatar.glb
   ↓
SkinnedMesh
   ↓
morphTargetInfluences
   ↓
shape keys
```

pero sustituyendo:

```text
random()
```

por:

```text
audio
 ↓
modelo neuronal
 ↓
expresión facial
 ↓
blendshapes
```

Es decir, hoy puedes tener:

```text
LLM
 ↓
TTS
 ↓
audio ──────────────┐
                    ↓
             Audio2Face
                    ↓
              blendshapes
                    ↓
                 GLB
```

y obtener una cara bastante convincente en tiempo real.

---

# 2. El siguiente problema ya no es la boca: es la **conducta**

Aquí está, en mi opinión, la parte realmente interesante del estado del arte.

Una persona hablando no hace simplemente:

```text
phoneme A → mouthOpen=0.7
phoneme B → mouthSmile=0.2
```

Hace simultáneamente:

```text
voz
+
parpadeo
+
mirada
+
cejas
+
movimientos de cabeza
+
respiración
+
postura
+
gesticulación
+
microgestos
```

Y además estos movimientos tienen relación con **lo que está diciendo**.

Por ejemplo:

> "Hay tres cosas importantes."

El avatar podría levantar ligeramente la cabeza, establecer contacto visual y acompañar el “tres” con un gesto.

Por tanto, el modelo moderno empieza a parecerse a:

```text
                   LLM
                    │
        ┌───────────┼────────────┐
        ↓           ↓            ↓
       TTS      intención      emoción
        │           │            │
        ↓           ↓            ↓
      audio      gestures     expression
        │           │            │
        └───────────┼────────────┘
                    ↓
             animation graph
                    ↓
                  avatar
```

Esto es exactamente el tipo de arquitectura que NVIDIA ACE está intentando convertir en una plataforma completa: habla, inteligencia, Audio2Emotion, Audio2Face, Animation Graph y renderizado. ([NVIDIA Docs][2])

---

# 3. El salto actual: el avatar **también observa**

Aquí cambia radicalmente el concepto.

Los sistemas más avanzados ya plantean:

```text
        PERSONA
        ↑     ↓
 cámara │     │ pantalla
 micro  │     │ altavoz
        ↓     ↑
     AI AVATAR
```

Por ejemplo, [Tavus](https://www.tavus.io/?utm_source=chatgpt.com) describe sus agentes actuales como sistemas capaces de **ver, escuchar y responder**, combinando percepción audiovisual, conversación, memoria y renderizado facial. ([Tavus][3])

Su arquitectura pública resulta especialmente interesante porque separa varios problemas:

```text
Phoenix
render facial

Raven
percepción multimodal

Sparrow
dinámica conversacional
```

Tavus afirma latencias de alrededor de **500 ms** para sus interfaces conversacionales. ([Tavus][4])

Ese orden de magnitud importa muchísimo.

Porque el realismo visual puede ser excelente, pero:

```text
Usuario: Hola
          ↓
       2.5 s
          ↓
Avatar: Hola
```

se siente inmediatamente artificial.

Mientras que un avatar visualmente más sencillo con:

```text
Usuario: Hola
          ↓
       300-600 ms
          ↓
Avatar: Hola
```

puede resultar mucho más natural.

---

# 4. HeyGen: de generación de vídeo a conversación

[HeyGen LiveAvatar](https://www.liveavatar.com/?utm_source=chatgpt.com) es otro ejemplo claro de hacia dónde está evolucionando el mercado.

Ya no se trata únicamente de generar vídeos de un presentador artificial.

LiveAvatar está diseñado específicamente para:

**conversaciones bidireccionales en tiempo real**, con lip-sync, expresiones, gestos e integración mediante API/SDK. ([HeyGen Centro de Ayuda][5])

Arquitectónicamente:

```text
micro
 ↓
STT
 ↓
LLM
 ↓
TTS
 ↓
LiveAvatar
 ↓
stream vídeo
```

Esto es muy cómodo como servicio, aunque introduce una diferencia fundamental respecto a una arquitectura propia con Three.js:

**recibes vídeo generado**, en lugar de controlar completamente un personaje 3D.

---

# 5. La frontera del fotorealismo: Meta Codec Avatars

En investigación, una referencia importante sigue siendo [Meta Codec Avatars](https://www.meta.com/emerging-tech/codec-avatars/?utm_source=chatgpt.com).

Aquí el objetivo es distinto:

> reconstruir una representación neural del humano que pueda ser conducida en tiempo real.

Meta lo plantea para **telepresencia fotorealista**, capturando mirada, expresiones, postura y gestos. ([Meta][6])

Su dataset Ava-256 es revelador sobre la escala del problema:

**256 personas y más de 200 millones de imágenes**, además de mallas, keypoints, segmentaciones y modelos de referencia. ([Meta][7])

Aquí ya no hablamos realmente de:

```text
mesh
+
texture
+
bones
+
blendshapes
```

sino cada vez más de:

```text
representación neural
+
latent expression
+
neural rendering
```

Es otra liga tecnológica.

---

# 6. 3D clásico frente a neural avatar

Esto nos deja dos ramas bastante diferentes.

### Avatar 3D

```text
GLB
├── mesh
├── skeleton
├── materials
├── morph targets
└── animations
```

Ventajas:

* render local;
* muy poca latencia;
* control absoluto;
* funciona en WebGL;
* barato;
* puedes modificar cualquier comportamiento.

Inconveniente:

el fotorealismo tiene límites.

### Neural avatar

```text
identity model
+
audio
+
expression conditioning
+
neural renderer
```

Ventaja:

**realismo extraordinario.**

Inconvenientes:

* GPU;
* coste;
* latencia;
* streaming;
* mucho menos control geométrico;
* dependencia frecuente de infraestructura cloud.

---

# 7. Y hay una tercera vía que me parece especialmente interesante

Un **avatar 3D estilizado pero conductualmente muy avanzado**.

Algo como:

```text
Three.js
+
GLB
+
skeleton
+
blendshapes
+
Audio2Face
+
procedural animation
+
LLM
```

Puede no parecer exactamente una persona real.

Pero puede **comportarse muchísimo más como una persona**.

Y ahí aparecen subsistemas que hoy tienen mucho recorrido:

```text
GazeController
BlinkController
HeadController
BreathingController
GestureController
ExpressionController
LipSyncController
IdleController
```

Todos actuando sobre el mismo `Actor`.

Eso además encaja extraordinariamente bien con la abstracción que estabas haciendo hoy de `Actor`, porque podrías llegar a algo así:

```javascript
class AIAvatar extends Actor {

    constructor(model) {
        super(model);

        this.gaze = new GazeController(this);
        this.face = new FaceController(this);
        this.gesture = new GestureController(this);
        this.speech = new SpeechController(this);
        this.idle = new IdleController(this);
    }

}
```

El avatar deja de ser un modelo 3D y pasa a ser **un agente encarnado**.

---

# 8. La arquitectura de referencia que yo estudiaría

Para un sistema web experimental:

```text
                        ┌──────────────┐
                        │    CAMERA    │
                        └──────┬───────┘
                               ↓
                        vision model
                               │
MIC → VAD → STT ───────────────┤
                               ↓
                             LLM
                               │
             ┌─────────────────┼──────────────────┐
             ↓                 ↓                  ↓
           text             emotion            intent
             │                 │                  │
             ↓                 ↓                  ↓
            TTS        facial expression       gestures
             │                 │                  │
             ↓                 ↓                  ↓
           audio           shape keys           bones
             │                 │                  │
             └──────────────┬──┴──────────────────┘
                            ↓
                       AIAvatar
                            ↓
                         THREE.JS
                            ↓
                          WebGL
```

Pero añadiría algo fundamental entre LLM y avatar:

```text
                 BEHAVIOR PLANNER
```

Por ejemplo, el LLM podría producir internamente:

```json
{
  "speech": "Exactamente. Hay tres posibilidades.",
  "emotion": "enthusiastic",
  "intensity": 0.45,
  "gaze": "user",
  "gesture": "enumerate",
  "head": "nod"
}
```

Y **el LLM no toca huesos directamente**.

Los controladores traducen eso a animación.

Esto evita el problema que precisamente estabas viendo hace un momento al hacer rotaciones aleatorias: movimientos físicamente posibles pueden parecer completamente antinaturales.

---

# 9. El verdadero estado del arte: sincronización multimodal

Creo que este es el concepto más importante de todos.

No es:

**LLM + avatar.**

Es:

### **multimodal behavioral synchronization**

Cuando habla:

```text
fonema
 └─ boca

prosodia
 ├─ cejas
 ├─ cabeza
 └─ expresión

semántica
 └─ gesto

turn-taking
 ├─ mirada
 ├─ postura
 └─ respiración

usuario
 ├─ mirada avatar
 ├─ reacción
 └─ interrupción
```

Eso es lo que empieza a diferenciar un avatar técnicamente impresionante de uno que realmente produce sensación de presencia.

---

## Dónde situaría el estado del arte en 2026

Simplificando mucho, veo **cuatro polos**:

| Enfoque              | Referencia                                                                                     | Fortaleza                 |
| -------------------- | ---------------------------------------------------------------------------------------------- | ------------------------- |
| Digital humans 3D    | [NVIDIA ACE](https://developer.nvidia.com/ace?utm_source=chatgpt.com)                          | control + tiempo real     |
| Video neural         | [HeyGen](https://www.heygen.com/?utm_source=chatgpt.com)                                       | realismo visual           |
| Agente audiovisual   | [Tavus](https://www.tavus.io/?utm_source=chatgpt.com)                                          | conversación multimodal   |
| Telepresencia neural | [Meta Codec Avatars](https://www.meta.com/emerging-tech/codec-avatars/?utm_source=chatgpt.com) | frontera de investigación |

Pero hay un espacio muy interesante entre todos ellos:

> **avatar 3D ligero + IA local/cloud + animación procedural inteligente + percepción multimodal.**

Y, teniendo ya Three.js + GLB + armature + shape keys funcionando, estás precisamente en un punto técnico desde el que se puede experimentar con esa arquitectura sin tener que resolver primero el renderizado.

De hecho, el siguiente experimento que haría no sería todavía conectar un LLM. Haría un **“cerebro motor” del avatar** que combine `Blink + Gaze + Breathing + Head + Gesture + Face`, con prioridades y *blending*, y después conectaríamos **audio → lip-sync/emoción** y finalmente **LLM → intención/gestos**. Ese recorrido permitiría comparar experimentalmente un avatar puramente aleatorio, uno reactivo al audio y uno semánticamente dirigido.

[1]: https://docs.nvidia.com/nim/digital-human/a2f-3d/latest/?utm_source=chatgpt.com "Audio2Face-3D NIM Documentation — NVIDIA NIM Audio2Face-3D"
[2]: https://docs.nvidia.com/ace/overview/2025.04.28/index.html?utm_source=chatgpt.com "NVIDIA ACE — ACE Overview"
[3]: https://www.tavus.io/?utm_source=chatgpt.com "Tavus: The Human Computing Company"
[4]: https://www.tavus.io/product/conversational-video?utm_source=chatgpt.com "Tavus: Meet the PALs"
[5]: https://help.heygen.com/en/articles/12758516-introducing-liveavatar?utm_source=chatgpt.com "Introducing LiveAvatar | HeyGen Help Center"
[6]: https://www.meta.com/emerging-tech/codec-avatars/?utm_source=chatgpt.com "Immersive Telepresence with Lifelike Avatars | Meta"
[7]: https://www.meta.com/emerging-tech/codec-avatars/ava256/?utm_source=chatgpt.com "Ava-256 Dataset | Meta"
