<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427083443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE external_api_tokens_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE external_api_tokens (id INT NOT NULL, user_id INT NOT NULL, service_name VARCHAR(64) NOT NULL, token VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3653D9DCA76ED395 ON external_api_tokens (user_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_service ON external_api_tokens (user_id, service_name)');
        $this->addSql('ALTER TABLE external_api_tokens ADD CONSTRAINT FK_3653D9DCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE auth_tokens DROP CONSTRAINT fk_auth_tokens_user');
        $this->addSql('DROP INDEX idx_auth_tokens_token');
        $this->addSql('ALTER TABLE auth_tokens ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE auth_tokens ADD CONSTRAINT FK_8AF9B66CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_auth_tokens_user_id RENAME TO IDX_8AF9B66CA76ED395');
        $this->addSql('ALTER TABLE likes DROP CONSTRAINT fk_likes_photo');
        $this->addSql('ALTER TABLE likes DROP CONSTRAINT fk_likes_user');
        $this->addSql('ALTER TABLE likes ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7D7E9E4C8C FOREIGN KEY (photo_id) REFERENCES photos (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_likes_user_id RENAME TO IDX_49CA4E7DA76ED395');
        $this->addSql('ALTER INDEX idx_likes_photo_id RENAME TO IDX_49CA4E7D7E9E4C8C');
        $this->addSql('ALTER TABLE photos DROP CONSTRAINT fk_photos_user');
        $this->addSql('ALTER TABLE photos ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE photos ADD CONSTRAINT FK_876E0D9A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_photos_user_id RENAME TO IDX_876E0D9A76ED395');
        $this->addSql('ALTER TABLE users ALTER id DROP DEFAULT');
        $this->addSql('ALTER INDEX users_username_key RENAME TO UNIQ_1483A5E9F85E0677');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE external_api_tokens_id_seq CASCADE');
        $this->addSql('ALTER TABLE external_api_tokens DROP CONSTRAINT FK_3653D9DCA76ED395');
        $this->addSql('DROP TABLE external_api_tokens');
        $this->addSql('ALTER TABLE auth_tokens DROP CONSTRAINT FK_8AF9B66CA76ED395');
        $this->addSql('CREATE SEQUENCE auth_tokens_id_seq');
        $this->addSql('SELECT setval(\'auth_tokens_id_seq\', (SELECT MAX(id) FROM auth_tokens))');
        $this->addSql('ALTER TABLE auth_tokens ALTER id SET DEFAULT nextval(\'auth_tokens_id_seq\')');
        $this->addSql('ALTER TABLE auth_tokens ADD CONSTRAINT fk_auth_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_auth_tokens_token ON auth_tokens (token)');
        $this->addSql('ALTER INDEX idx_8af9b66ca76ed395 RENAME TO idx_auth_tokens_user_id');
        $this->addSql('ALTER TABLE photos DROP CONSTRAINT FK_876E0D9A76ED395');
        $this->addSql('CREATE SEQUENCE photos_id_seq');
        $this->addSql('SELECT setval(\'photos_id_seq\', (SELECT MAX(id) FROM photos))');
        $this->addSql('ALTER TABLE photos ALTER id SET DEFAULT nextval(\'photos_id_seq\')');
        $this->addSql('ALTER TABLE photos ADD CONSTRAINT fk_photos_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_876e0d9a76ed395 RENAME TO idx_photos_user_id');
        $this->addSql('CREATE SEQUENCE users_id_seq');
        $this->addSql('SELECT setval(\'users_id_seq\', (SELECT MAX(id) FROM users))');
        $this->addSql('ALTER TABLE users ALTER id SET DEFAULT nextval(\'users_id_seq\')');
        $this->addSql('ALTER INDEX uniq_1483a5e9f85e0677 RENAME TO users_username_key');
        $this->addSql('ALTER TABLE likes DROP CONSTRAINT FK_49CA4E7DA76ED395');
        $this->addSql('ALTER TABLE likes DROP CONSTRAINT FK_49CA4E7D7E9E4C8C');
        $this->addSql('CREATE SEQUENCE likes_id_seq');
        $this->addSql('SELECT setval(\'likes_id_seq\', (SELECT MAX(id) FROM likes))');
        $this->addSql('ALTER TABLE likes ALTER id SET DEFAULT nextval(\'likes_id_seq\')');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT fk_likes_photo FOREIGN KEY (photo_id) REFERENCES photos (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_49ca4e7d7e9e4c8c RENAME TO idx_likes_photo_id');
        $this->addSql('ALTER INDEX idx_49ca4e7da76ed395 RENAME TO idx_likes_user_id');
    }
}
