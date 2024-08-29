CREATE EVENT `%( DB_NAME )%`.`session_cleaner`
ON SCHEDULE EVERY 1 HOUR
STARTS '2023-12-12 00:00:00'
DO
    DELETE
    FROM
        `%( DB_NAME )%`.`session`
    WHERE
        `datetime.expiration` <= CURRENT_TIMESTAMP
;