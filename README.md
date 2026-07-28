# Sistema de Evaluación de Competencias con Inteligencia Artificial (IA)

Plataforma web desarrollada en **PHP** y **MySQL** diseñada para automatizar y optimizar la evaluación del desarrollo de competencias laborales y profesionales de aprendices, integrando de forma inteligente la API de **Google Gemini** para la retroalimentación cualitativa y cuantitativa de casos prácticos.

---

##  Tabla de Contenidos
1. [¿Qué hace la aplicación?](#-qué-hace-la-aplicación)
2. [Características Principales](#-características-principales)
3. [Arquitectura y Base de Datos](#-arquitectura-y-base-de-datos)
4. [Requisitos del Sistema](#-requisitos-del-sistema)
5. [Instalación y Configuración](#-instalación-y-configuración)
6. [Cómo Ejecutar la Aplicación](#-cómo-ejecutar-la-aplicación)

---

## ¿Qué hace la aplicación?

El **Sistema de Evaluación de Competencias con IA** funciona como un entorno de aprendizaje y certificación inteligente:

* **Gestión de Usuarios y Roles:** Control de acceso diferenciado para aprendices, instructores o administradores.
* **Evaluación de Casos Prácticos:** Los usuarios presentan pruebas basadas en escenarios del mundo real vinculados a competencias específicas.
* **Calificación y Análisis con IA:** Las respuestas enviadas por los aprendices son procesadas y enviadas en tiempo real a la API de **Google Gemini**, la cual actúa como un instructor experto emitiendo:
  * Un puntaje porcentual y nivel de dominio (*Inicial*, *Intermedio*, *Avanzado*).
  * Fortalezas detectadas.
  * Oportunidades de mejora.
  * Recomendaciones profesionales y análisis general detallado.
* **Seguimiento del Progreso:** Visualización del avance individual por competencia a través de tablas de control y métricas de desempeño.
* **Emisión de Certificados:** Generación automática de constancias descargables en PDF para aquellos aprendices que superen el puntaje mínimo aprobatorio (**≥ 70%**).

---

## Características Principales

* **Evaluación Dinámica:** Conexión segura mediante cURL con el modelo de lenguaje de Google (Gemini).
* **Respuestas Estrictas en JSON:** Configuración de la IA para retornar estructuras de datos limpias y procesables por la base de datos relacional.
* **Dashboard Interactivo:** Paneles adaptados para el seguimiento visual del progreso.
* **Seguridad:** Manejo seguro de sesiones de usuario, encriptación de credenciales y protección de la API Key mediante archivos de configuración dedicados (`config.php`).

---

##  Arquitectura y Base de Datos

La aplicación utiliza una base de datos MySQL llamada `competencias_ia` estructurada en tablas relacionales clave:
* `personas`: Almacena la información de los usuarios y aprendices.
* `competencias`: Define los módulos de aprendizaje o áreas a evaluar.
* `casos` y `preguntas`: Contienen los reactivos y escenarios prácticos de las pruebas.
* `respuestas`: Almacena el historial de las respuestas emitidas por los aprendices.
* `analisis_ia`: Guarda la retroalimentación cualitativa devuelta por el modelo.
* `progreso`: Registra el puntaje actual (`nivel_actual`), inicial y fecha de última actualización.

---

##  Requisitos del Sistema

Para ejecutar este proyecto de manera local, asegúrate de contar con:
* **Servidor Web Local** (como XAMPP, WampServer o Laragon).
* **PHP** (versión 8.0 o superior recomendada).
* **MySQL / MariaDB**.
* Extensión **cURL** de PHP habilitada.
* Una clave de API válida para la API de Google Gemini (`GEMINI_API_KEY`).

---

## Instalación y Configuración

1. **Clona el repositorio:**
   ```bash
   git clone [https://github.com/sebas90-cpu/sistemascompetenciasia.git](https://github.com/sebas90-cpu/sistemascompetenciasia.git)
