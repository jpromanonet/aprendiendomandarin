# Aprendiendo mandarín

Sitio para estudiar chino mandarín (pǔtōnghuà) siguiendo los nueve niveles del HSK. Incluye clases con audio, diccionario, cuenta, progreso y certificados de constancia. No reemplaza el examen oficial y los certificados no tienen validez oficial.

Hecho en PHP, HTML, CSS y JavaScript, con MySQL. No usa framework.

## Qué hay

- Nueve niveles en tres etapas, con clases, vocabulario, ejemplos, práctica y autoevaluación.
- Diccionario con las palabras del programa HSK 3.0, búsqueda y filtro por nivel.
- Cuenta con email y contraseña. El ingreso con Google es opcional.
- Progreso por clase y certificados de nivel. El certificado final aparece cuando están los nueve.
- Libros en PDF, con portada, para descargar desde la sección Libros.

## Requisitos

- PHP 8.3 o superior, con `pdo_mysql`, `mbstring`, `curl` y `json`.
- MySQL 8 o MariaDB.
- Un servidor web con el proyecto en una carpeta pública. En este repo las rutas pasan por `index.php` (`/aprendiendomandarin/index.php/diccionario`).

## Instalación

1. Copiá `config.example.php` a `config.php`.
2. Completá la base, la URL pública y una `seed_key` propia. `config.php` no se sube al repositorio.
3. Abrí en el navegador:

```text
/aprendiendomandarin/index.php/instalar?key=TU_SEED_KEY
```

Eso crea la base si no existe, arma las tablas y carga niveles, clases y diccionario. Volver a correrlo vacía el progreso, las autoevaluaciones y los certificados. No borra usuarios ni libros.

4. Entrá a la cuenta y registrate.

## Audio

Los MP3 no están en el repositorio. Se generan con [edge-tts](https://github.com/rany2/edge-tts), voz `zh-CN-XiaoxiaoNeural`:

```bash
pip install edge-tts
python scripts/generate_audio.py
```

Los archivos quedan en `assets/audio/`. Si falta un audio, el botón de escuchar no tiene archivo.

## Google

En `config.php`, completá `client_id` y `client_secret` de un cliente OAuth de tipo aplicación web. La URI de redirección tiene que coincidir exactamente con `google.redirect`, por ejemplo:

```text
http://localhost/aprendiendomandarin/index.php/auth/google/callback
```

Sin esas claves, el botón de Google no inicia sesión. El email y la contraseña siguen funcionando.

## Libros

Los PDF y las portadas no se suben desde la web ni van al repositorio. Van en `storage/libros/`:

- `mi-libro.pdf`
- `mi-libro.jpg` (la portada, mismo nombre)

Después, una fila en la tabla `books`:

```sql
INSERT INTO books (title, description, file_name, size_label)
VALUES ('Título', 'Descripción corta.', 'mi-libro.pdf', '2,2 MB');
```

## Qué no se versiona

- `config.php`
- `storage/libros/` y `storage/avatars/`
- `assets/audio/*.mp3`

## Léxico

El vocabulario HSK (hanzi, pinyin, sentidos en inglés y ejemplos) lo armó [Gushi](https://gushichinese.com/hsk-3.0/downloads) a partir del programa de noviembre de 2025.

- Sentidos en inglés: CC-CEDICT, [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0/).
- Ejemplos: en gran parte Tatoeba, [CC BY 2.0 FR](https://creativecommons.org/licenses/by/2.0/fr/).
- El listado de palabras y la derivación al español de este sitio se comparten bajo CC BY-SA 4.0.

El detalle está en `data/CREDIT.txt`.
