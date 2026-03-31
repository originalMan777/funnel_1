<?php

namespace Tests\Regression\Routes;

use App\Http\Controllers\Admin\AiPostImporterController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AiImporterRouteRegressionTest extends TestCase
{
    public function test_importer_route_names_are_defined_with_the_expected_methods_and_targets(): void
    {
        $indexRoute = Route::getRoutes()->getByName('admin.post-importer.index');
        $storeRoute = Route::getRoutes()->getByName('admin.post-importer.store');

        $this->assertNotNull($indexRoute);
        $this->assertNotNull($storeRoute);

        $this->assertSame('admin/post-importer', $indexRoute->uri());
        $this->assertSame('admin/post-importer', $storeRoute->uri());

        $this->assertSame(AiPostImporterController::class . '@index', $indexRoute->getActionName());
        $this->assertSame(AiPostImporterController::class . '@store', $storeRoute->getActionName());

        $this->assertContains('GET', $indexRoute->methods());
        $this->assertContains('HEAD', $indexRoute->methods());
        $this->assertSame(['POST'], $storeRoute->methods());
    }

    public function test_named_importer_routes_generate_the_expected_urls(): void
    {
        $this->assertSame(url('/admin/post-importer'), route('admin.post-importer.index'));
        $this->assertSame(url('/admin/post-importer'), route('admin.post-importer.store'));
    }
}
