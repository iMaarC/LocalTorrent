# LocalTorrent
Requisitos LocalTorrent

1. Servidor Web (PHP, Mysql)
  - En la carpeta lib/DataBase se encuentran dos archivos: BaseScript.sql, el cual hay que ejecutar para crear la base de datos.
  - Por otro lado esta el archivo DBConfig.txt el cual es la configuración de la base de datos.
2. Tranmission [Pagina Oficial](https://transmissionbt.com/download/)
  - **Windows / MacOs**
    - Instalar Transmission
    - En configuración / preferencias habilitar el acceso remoto.
    ![Ajustes Transmission](https://i.imgur.com/PeHvR6S.png "Ajustes Transmission")
  - **Linux**
    - Instalar el servicio de Transmission
      - sudo apt-get update
      - sudo apt-get install transmission-daemon
      - En el archivo de configuración '/etc/transmission-daemon/settings.json' poner las siguientes directivas.
        - "rpc-enabled": true
        - "umask": 000
        - "rpc-authentication-required": false
