<?php
// ---
// slug: secure-a-resource-with-custom-voters
// name: Secure a Resource with Custom Voters
// position: 10
// executable: true
// ---

namespace App\OpenApi {
    use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
    use ApiPlatform\OpenApi\OpenApi;
    use ApiPlatform\OpenApi\Model;
    use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
    use Symfony\Component\DependencyInjection\Attribute\MapDecorated;

    #[AsDecorator(decorates: 'api_platform.openapi.factory')]
    final class OpenApiFactory implements OpenApiFactoryInterface
    {
        private $decorated;

        public function __construct(#[MapDecorated] OpenApiFactoryInterface $decorated)
        {
            $this->decorated = $decorated;
        }

        public function __invoke(array $context = []): OpenApi
        {
            $openApi = $this->decorated->__invoke($context);
//            $pathItem = $openApi->getPaths()->getPath('/api/grumpy_pizzas/{id}');
//            $operation = $pathItem->getGet();
//
//            $openApi->getPaths()->addPath('/api/grumpy_pizzas/{id}', $pathItem->withGet(
//                $operation->withParameters(array_merge(
//                    $operation->getParameters(),
//                    [new Model\Parameter('fields', 'query', 'Fields to remove of the output')]
//                ))
//            ));

            $openApi = $openApi->withInfo((new Model\Info('New Title', 'v2', 'Description of my custom API'))->withExtensionProperty('info-key', 'Info value'));
            $openApi = $openApi->withExtensionProperty('key', 'Custom x-key value');
            $openApi = $openApi->withExtensionProperty('x-value', 'Custom x-value value');

            return $openApi;
        }
    }
}

// namespace App\DependencyInjection {
//     use App\OpenApi\OpenApiFactory;
//     use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
//     use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
//
//     function configure(ContainerConfigurator $configurator) {
//         $services = $configurator->services();
//         $services->set(OpenApiFactory::class)
//             ->decorate('api_platform.openapi.factory')
//             ->args([service('.inner')])
//             ->autowire(true)
//         ;
//     }
// }

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/docs.json');
    }

    // class MyTest extends ApiTestCase {
    //     public function testDoc() {
    //         $req = $this->request('/docs.json');
    //         $this->assertEquals('New Tilte', $req->json()['title']);
    //     }
    //
    // }
}
