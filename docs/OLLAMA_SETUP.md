# Configuración de Ollama (IA Local) para SKMS-Unimar

Este documento explica cómo configurar el modelo local **Qwen 2.5 (1.5b)** para servir como fallback offline cuando no haya internet o la API de Groq falle.

---

## 1. Instalación de Ollama

1. Descarga el instalador oficial de Ollama para Windows desde:  
   👉 [https://ollama.com/download](https://ollama.com/download)
2. Ejecuta el archivo `.exe` descargado e instálalo normalmente.
3. Al finalizar la instalación, Ollama se ejecutará en segundo plano (verás una pequeña llama en la barra de tareas de Windows).

---

## 2. Descargar y ejecutar el modelo Qwen 2.5 (1.5B)

El modelo **Qwen 2.5 (1.5B)** es extremadamente ligero (pesa solo ~950 MB en disco) y consume muy poca RAM (~1.5 GB), por lo que correrá de forma fluida en tu procesador Core i3 sin ralentizar tu computadora.

1. Abre una terminal de Windows (PowerShell o CMD).
2. Ejecuta el siguiente comando para descargar y correr el modelo:
   ```bash
   ollama run qwen2.5:1.5b
   ```
3. Ollama comenzará a descargar el modelo. Una vez finalizada la descarga, verás una interfaz interactiva de chat. Puedes cerrarla con `/bye`.
4. El servidor de Ollama se mantendrá activo escuchando en el puerto local `http://localhost:11434`.

---

## 3. Configuración del Entorno (.env)

Asegúrate de tener las siguientes variables configuradas en tu archivo `.env` del proyecto:

```env
# Configuración del Fallback de Ollama
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=qwen2.5:1.5b
```

---

## 4. Probando el Fallback Offline

Para probar el funcionamiento offline:
1. Desactiva temporalmente el internet de tu computadora o simplemente quita la variable `GROQ_API_KEY` en tu `.env`.
2. Sube una tesis o ejecuta el comando de extracción.
3. El sistema detectará que Groq no está disponible y recurrirá automáticamente a tu servidor local de Ollama para realizar la extracción en unos 10-15 segundos.
