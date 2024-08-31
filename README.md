# MonitorClient

**MonitorClient** es una herramienta para capturar y enviar logs a un servidor centralizado usando WebSockets y HTTP. Diseñada para ser utilizada en entornos corporativos, esta herramienta permite a las empresas monitorear y gestionar sus logs de manera eficiente.

## Instalación

Para instalar **MonitorClient**, sigue estos pasos:

1. **Clona el repositorio**:

    ```bash
    git clone https://github.com/tuusuario/devquick-monitor-client.git
    cd devquick-monitor-client
    ```

2. **Instala las dependencias**:

    Asegúrate de tener [Composer](https://getcomposer.org/) instalado en tu sistema. Luego, ejecuta:

    ```bash
    composer install
    ```

3. **Configura el paquete**:

    Crea un archivo de configuración en el directorio raíz del proyecto llamado `.env` y agrega las siguientes variables:

    ```env
    SERVER_URL=https://centralized-log-server.com/logs
    JWT_SECRET=your_jwt_secret_key
    ACCESS_KEY=your_access_key
    ```

## Uso

Para utilizar el cliente de monitoreo, sigue estos pasos:

1. **Configura el Cliente**:

    En tu aplicación, incluye y configura el cliente **MonitorClient**:

    ```php
    require 'vendor/autoload.php';

    use DevQuick\MonitorClient\MonitorClient;

    $monitorClient = new MonitorClient();
    ```

2. **Captura Logs**:

    Utiliza el método `log` para capturar y enviar logs:

    ```php
    $monitorClient->log('info', 'This is an informational message.');
    ```

## Licencia

**MonitorClient** está bajo una licencia de código cerrado. La licencia permite el uso del software únicamente para fines corporativos internos de la empresa que adquiere la licencia. No se permite modificar, redistribuir ni utilizar el software fuera del ámbito corporativo de la empresa licenciada. Para más detalles, consulta el archivo [LICENSE](LICENSE).

## Contribuciones

Este proyecto no acepta contribuciones externas. Para consultas o soporte, por favor contacta a Desarrollo Ágil Digital SAS a través de [correo electrónico](mailto:support@desarrolloagil.com).

## Soporte

Para soporte y asistencia técnica, por favor contacta a Desarrollo Ágil Digital SAS en:

- **Correo electrónico**: [tecnologia@devquick.co](mailto:soporte@devquick.co)
- **Teléfono**: +57 (1) 3105951704
