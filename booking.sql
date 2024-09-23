database booking
create table users

(
    id       int auto_increment
        primary key,
    email    varchar(255)         not null,
    password varchar(255)         not null,
    is_admin tinyint(1) default 0 null,
    constraint email
        unique (email)
);

create table bookings
(
    id        int auto_increment
        primary key,
    user_id   int          not null,
    date      date         not null,
    slot_time time         not null,
    name      varchar(255) not null,
    constraint user_id
        unique (user_id, date),
    constraint bookings_ibfk_1
        foreign key (user_id) references users (id)
            on delete cascade
);

