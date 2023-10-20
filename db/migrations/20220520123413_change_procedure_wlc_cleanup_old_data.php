<?php

use Phinx\Migration\AbstractMigration;

class ChangeProcedureWlcCleanupOldData extends AbstractMigration
{
    public function up() {
        $this->execute('DROP PROCEDURE IF EXISTS `WlcCleanupOldData`');

        $this->execute("
            CREATE PROCEDURE `WlcCleanupOldData`()
                MODIFIES SQL DATA
                SQL SECURITY INVOKER
            BEGIN
                DECLARE v_delete_limit INT DEFAULT 1000;
                DECLARE v_row_count INT DEFAULT 0;

                SELECT 'Cleaning ApiRequests...';
                REPEAT

                    START TRANSACTION;
                    DELETE FROM api_requests
                        WHERE `Date` < DATE_SUB( NOW(), INTERVAL 1 MONTH )
                        LIMIT v_delete_limit;
                    SET v_row_count = ROW_COUNT();
                    COMMIT;

                    SELECT CONCAT( '... deleted ', v_row_count );
                UNTIL v_row_count < v_delete_limit
                END REPEAT;
            END
        ");
    }

    public function down() {
    }
}
