CREATE EVENT `%( DB_NAME )%`.`access_cleaner`
ON SCHEDULE EVERY 1 HOUR
STARTS '2023-12-12 00:00:00'
DO
    DELETE
    FROM
        `%( DB_NAME )%`.`access`
    WHERE
        `datetime.insert` < CURRENT_TIMESTAMP - INTERVAL 1 MONTH
;