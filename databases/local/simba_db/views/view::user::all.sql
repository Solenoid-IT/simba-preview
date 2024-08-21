CREATE OR REPLACE VIEW `%( DB_NAME )%`.`view::user::all` AS

SELECT
    user_table.`id` AS `id`,
    user_table.`username` AS `username`,
    user_table.`email` AS `email`,
    user_table.`profile.name` AS `profile.name`,
    user_table.`profile.surname` AS `profile.surname`,
    user_table.`profile.photo` AS `profile.photo`,
    user_table.`security.password` AS `security.password`,
    user_table.`security.mfa` AS `security.mfa`,
    user_table.`security.idk.public_key` AS `security.idk.public_key`,
    user_table.`security.idk.signature` AS `security.idk.signature`,
    user_table.`security.idk.authentication` AS `security.idk.authentication`,
    user_table.`datetime.insert` AS `datetime.insert`,

    hierarchy_table.`id` AS `hierarchy.id`,
    hierarchy_table.`type` AS `hierarchy.type`
FROM
    `%( DB_NAME )%`.`user` user_table
        INNER JOIN
    `%( DB_NAME )%`.`hierarchy` hierarchy_table
        ON
    (
        user_table.`hierarchy` = hierarchy_table.`id`
    )
ORDER BY
    user_table.`id` DESC
;