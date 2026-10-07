[English](README.md) | **Español** | [Português Brasileiro](README-pt_BR.md)

# Pruebas Cypress

Las pruebas usan OMP 3.4 y Thoth desechables, sin modificar la instalación local
ni escribir en las APIs públicas de Thoth.

## Requisitos

Docker Compose v2, Python 3.9+, Linux/amd64 (o emulación compatible) y un dataset
MySQL de OMP `stable-3_4_0`, contexto `publicknowledge`, con `database.sql`, `files/`
y `public/` de la misma instantánea.

## Preparar y ejecutar

```sh
python3 tests/environment/environment.py prepare --dataset /path/to/omp-dataset --apply
python3 tests/environment/environment.py run --apply
```

`prepare` construye las imágenes, inicia o reanuda todos los servicios, renueva las
credenciales cuando sea necesario y restaura el dataset OMP desechable. Reemplaza
los datos de prueba OMP sin modificar el dataset original. Conserva publisher e imprint de Thoth.

`run` ejecuta la suite una vez; añada `--spec ThothRegistration.cy.js` para una spec.
Después de preparar, use `open --apply` para la interfaz gráfica. Requiere
X11/XWayland y `xauth` en la misma sesión gráfica. El contenedor accede a X11;
utilice imágenes y pruebas de confianza. Los cambios en specs no requieren reconstrucción.
Cierre Cypress antes de otro comando; cerrarlo mantiene los servicios activos.

`open` y `run` verifican la conexión autenticada OMP/Thoth sin usar la caché del plugin.
Reutilizan los datos sin restaurarlos implícitamente. Ante un fallo, ejecute `prepare`.
Después de reiniciar el equipo, `prepare` reanuda Thoth y restaura OMP.

Sin `--apply`, los comandos de modificación solo muestran el plan. `status` consulta
los contenedores. `down --apply` elimina los servicios, volúmenes y credenciales
desechables de este proyecto. Ejecute `prepare` para empezar de nuevo.

## Credenciales y CI

Los tokens de prueba duran dos días. La preparación y el inicio de la API renuevan
tokens vencidos, revocados o con menos de una hora restante. La identidad de la API
y el PAT administrativo de larga duración quedan en el volumen privado `bootstrap`.
OMP recibe solamente el volumen `client` en lectura. `down` elimina ambos.

En CI, `/builds` se comparte con el job: los archivos administrativos son accesibles
hasta la limpieza final. La separación por volúmenes corresponde a Compose local.
CI utiliza los mismos comandos internos `prepare` y `run`, ejecutando la suite dos veces.

Los entornos antiguos requieren `down --apply` una vez antes del nuevo `prepare`,
porque no conservaban la clave de la API. Se eliminaron `up`, `cypress` y `smoke`;
utilice `prepare`, `run` y `status`.

## Cobertura del registro

`ThothRegistration.cy.js` prepara en OMP un libro publicado con metadatos completos,
lo registra mediante la interfaz y verifica los datos persistidos en Thoth con una
consulta GraphQL independiente. Cubre títulos, resúmenes y biografías bilingües; DOI,
fecha, edición, lugar, páginas e imágenes; licencia, derechos y URL de portada; autoría,
ORCID y sitio web; idioma, temas y referencias; PDF, EPUB e impreso,
ISBN, accesibilidad y enlaces digitales; y un capítulo con DOI, páginas y autoría.

La fixture completa es exclusiva de este caso. Las normas y la excepción de accesibilidad
se prueban en formatos digitales distintos, conforme a Thoth. La carga/alojamiento de
portada en S3 y los archivos/enlaces propios de capítulos quedan fuera de este escenario.
La cobertura abarca las familias de metadatos, no todas las combinaciones de valores.

En este core los temas son cadenas; la fixture utiliza prefijos de clasificación.
No existe el campo estructurado de afiliación ROR: el test comprueba que no se inventa
una afiliación. Después de publicar, se comprueba el estado Thoth al recargar la página.
Para versiones simultáneas, use un `--port` diferente al preparar cada entorno por primera vez.
