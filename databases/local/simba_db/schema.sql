SET default_storage_engine = INNODB;

CREATE DATABASE `%( DB_NAME )%`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci
;

USE `%( DB_NAME )%`;






CREATE TABLE `hierarchy`
(
    `id`                                 BIGINT UNSIGNED AUTO_INCREMENT                           NOT NULL,

    `type`                               VARCHAR(255)                                             NOT NULL,
    `color`                              VARCHAR(255)                                                 NULL,

    `datetime.insert`                    TIMESTAMP                      DEFAULT CURRENT_TIMESTAMP NOT NULL,



    PRIMARY KEY (`id`),

    UNIQUE  KEY (`type`)
)
;

CREATE TABLE `user`
(
    `id`                                  BIGINT UNSIGNED AUTO_INCREMENT                           NOT NULL,

    `hierarchy`                           BIGINT UNSIGNED                                          NOT NULL,

    `username`                            VARCHAR(255)                                             NOT NULL,
    `email`                               VARCHAR(255)                                             NOT NULL,

    `profile.name`                        VARCHAR(255)                                                 NULL,
    `profile.surname`                     VARCHAR(255)                                                 NULL,
    `profile.photo`                       MEDIUMBLOB                                                   NULL,

    `security.password`                   VARCHAR(255)                                                 NULL,
    `security.mfa`                        BOOLEAN                                    DEFAULT FALSE NOT NULL,

    `security.idk.public_key`             LONGBLOB                                                     NULL,
    `security.idk.signature`              LONGBLOB                                                     NULL,
    `security.idk.authentication`         BOOLEAN                                    DEFAULT FALSE NOT NULL,

    `datetime.insert`                     TIMESTAMP                      DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `datetime.changelog_mark_as_read`     TIMESTAMP                                                    NULL,



    PRIMARY KEY (`id`),

    UNIQUE  KEY (`username`),
    UNIQUE  KEY (`email`),

    FOREIGN KEY (`hierarchy`)
    REFERENCES `hierarchy` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
)
;



CREATE TABLE `session`
(
    `id`                                 VARCHAR(255)                                             NOT NULL,

    `data`                               LONGBLOB                                                 NOT NULL,

    `user`                               BIGINT UNSIGNED                                              NULL,

    `datetime.creation`                  TIMESTAMP                                                NOT NULL,
    `datetime.expiration`                TIMESTAMP                                                NOT NULL,



    PRIMARY KEY (`id`),

    FOREIGN KEY (`user`)
    REFERENCES `user` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
)
;

CREATE TABLE `access`
(
    `login_method`                       VARCHAR(255)                                             NOT NULL,

    `ip.address`                         VARCHAR(255)                                             NOT NULL,
    `ip.country.code`                    VARCHAR(255)                                             NOT NULL,
    `ip.country.name`                    VARCHAR(255)                                             NOT NULL,
    `ip.isp`                             VARCHAR(255)                                             NOT NULL,

    `user_agent`                         LONGTEXT                                                 NOT NULL,

    `browser`                            VARCHAR(255)                                             NOT NULL,
    `os`                                 VARCHAR(255)                                             NOT NULL,
    `hw`                                 VARCHAR(255)                                             NOT NULL,

    `user`                               BIGINT UNSIGNED                                              NULL,
    `session`                            VARCHAR(255)                                                 NULL,

    `datetime.insert`                    TIMESTAMP                      DEFAULT CURRENT_TIMESTAMP NOT NULL,



    FOREIGN KEY (`user`)
    REFERENCES `user` (`id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL,

    FOREIGN KEY (`session`)
    REFERENCES `session` (`id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL
)
;



CREATE TABLE `document`
(
    `id`                                 BIGINT UNSIGNED AUTO_INCREMENT                           NOT NULL,

    `owner`                              BIGINT UNSIGNED                                              NULL,

    `path`                               VARCHAR(255)                                             NOT NULL,

    `title`                              VARCHAR(255)                                             NOT NULL,
    `description`                        TEXT                                                     NOT NULL,

    `content`                            LONGTEXT                                                 NOT NULL,

    `datetime.insert`                    TIMESTAMP                      DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `datetime.update`                    TIMESTAMP                                                    NULL ON UPDATE CURRENT_TIMESTAMP,

    `datetime.option.active`             TIMESTAMP                                                    NULL,
    `datetime.option.sitemap`            TIMESTAMP                                                    NULL,



    PRIMARY KEY (`id`),

    UNIQUE  KEY (`path`),
    
    FOREIGN KEY (`owner`)
    REFERENCES `user` (`id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL
)
;

CREATE TABLE `tag`
(
    `id`                                 BIGINT UNSIGNED AUTO_INCREMENT                           NOT NULL,

    `value`                              VARCHAR(255)                                             NOT NULL,
    `color`                              VARCHAR(255)                   DEFAULT '#b1b1b1'         NOT NULL,

    `datetime.insert`                    TIMESTAMP                      DEFAULT CURRENT_TIMESTAMP NOT NULL,



    PRIMARY KEY (`id`),

    UNIQUE  KEY (`value`)
)
;



CREATE TABLE `document.tag`
(
    `document`                                 BIGINT UNSIGNED                                          NOT NULL,
    `tag`                                      BIGINT UNSIGNED                                          NOT NULL,



    PRIMARY KEY (`document`,`tag`),
    
    FOREIGN KEY (`document`)
    REFERENCES `document` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

    FOREIGN KEY (`tag`)
    REFERENCES `tag` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
)
;



CREATE TABLE `visitor`
(
    `route`                              TEXT                                                     NOT NULL,

    `document`                           BIGINT UNSIGNED                                          NOT NULL,

    `ip.address`                         VARCHAR(255)                                             NOT NULL,
    `ip.country.code`                    VARCHAR(255)                                             NOT NULL,
    `ip.country.name`                    VARCHAR(255)                                             NOT NULL,
    `ip.isp`                             VARCHAR(255)                                             NOT NULL,

    `user_agent`                         LONGTEXT                                                 NOT NULL,

    `browser`                            VARCHAR(255)                                             NOT NULL,
    `os`                                 VARCHAR(255)                                             NOT NULL,
    `hw`                                 VARCHAR(255)                                             NOT NULL,

    `datetime.insert`                    TIMESTAMP                      DEFAULT CURRENT_TIMESTAMP NOT NULL
)
;



CREATE TABLE `authorization`
(
    `token`                              VARCHAR(255)                                             NOT NULL,

    `callback_url`                       TEXT                                                         NULL,

    `data`                               LONGBLOB                                                     NULL,

    `datetime.insert`                    TIMESTAMP                      DEFAULT CURRENT_TIMESTAMP NOT NULL,
    `datetime.expiration`                TIMESTAMP                                                NOT NULL,



    PRIMARY KEY (`token`)
)
;