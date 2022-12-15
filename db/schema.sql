create table if not exists usuario (
    id integer primary key auto_increment,
    nombre varchar(10) not null,
    clave varchar(200) not null,
    constraint unique_nombre unique (nombre)
) engine=InnoDB;

create table if not exists imagen (
    id integer primary key auto_increment,
    nombre varchar(200) not null,
    ruta varchar(200) not null,
    subido integer not null default UNIX_TIMESTAMP(),
    usuario integer not null,
    constraint pk_usuario_usuario foreign key (usuario) references usuario (id)
) engine=InnoDB;
