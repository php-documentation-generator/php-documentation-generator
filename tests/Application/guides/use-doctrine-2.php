<?php

// Should be a real guide

namespace App\Entity {

    use ApiPlatform\Metadata\ApiResource;
    use Doctrine\ORM\Mapping as ORM;

    /**
     * Book.
     *
     * @author Antoine Bluchet <soyuka@gmail.com>
     */
    #[ApiResource]
    #[ORM\Entity]
    class Book
    {
        #[ORM\Column(type: 'integer')]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'AUTO')]
        private $id;
        #[ORM\Column]
        public $name;
        #[ORM\Column(unique: true)]
        public $description;

        public function getId()
        {
            return $this->id;
        }
    }
}

namespace App\Playground {
    use App\Kernel;
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        $body = [
            'name' => 'bookToto',
            'description' => 'abcd'
        ];
        return Request::create('/books.jsonld', 'POST',[], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($body));
    }

    function setup(Kernel $kernel): void
    {
        $kernel->executeMigrations();
    }
}

namespace App\Tests {
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
    use App\Entity\Book;
    use App\Kernel;
    use Symfony\Component\HttpKernel\KernelInterface;
    use Zenstruck\Foundry\Test\ResetDatabase;

    final class BookTest extends ApiTestCase
    {
        public function setUp(): void
        {
            static::createKernel()->executeMigrations();
        }

        public function testPostBook(): void
        {
            $response = static::createClient()->request('POST', '/books', ['json' => [
                'description' => '0099740915',
                'name' => 'The Handmaid\'s Tale'
            ]]);

            $this->assertResponseStatusCodeSame(201);
            $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
            $this->assertJsonContains([
                '@context' => '/contexts/Book',
                '@type' => 'Book',
                'description' => '0099740915',
                'name' => 'The Handmaid\'s Tale'
            ]);
            $this->assertMatchesRegularExpression('~^/books/\d+$~', $response->toArray()['@id']);
            $this->assertMatchesResourceItemJsonSchema(Book::class);
        }
    }
}

namespace DoctrineMigrations {

    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\Migrations\AbstractMigration;

    final class Migration extends AbstractMigration
    {
        public function up(Schema $schema): void
        {
            $this->addSql('CREATE TABLE book (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_CBE5A331CC1CF4E6 ON book (description)');
        }

        public function down(Schema $schema): void
        {
            $this->addSql('DROP TABLE book');
        }
    }
}

namespace App\Fixtures {
    use App\Entity\Book;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Persistence\ObjectManager;
    use Zenstruck\Foundry\AnonymousFactory;
    use function Zenstruck\Foundry\faker;

    final class BookFixtures extends Fixture
    {
        public function load(ObjectManager $manager): void
        {
            $factory = AnonymousFactory::new(Book::class);
            $factory->many(20)->create(static function (int $i): array {
                return [
                    'name' => faker()->name,
                    'description' => faker()->isbn10()
                ];
            });
        }
    }
}