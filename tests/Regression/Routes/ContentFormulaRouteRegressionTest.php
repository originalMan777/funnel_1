<?php

namespace Tests\Regression\Routes;

use App\Http\Controllers\ContentFormula\ContentFormulaController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ContentFormulaRouteRegressionTest extends TestCase
{
    public function test_content_formula_routes_are_defined_with_expected_methods_and_targets(): void
    {
        $index = Route::getRoutes()->getByName('admin.content-formula.index');
        $generate = Route::getRoutes()->getByName('admin.content-formula.generate');
        $config = Route::getRoutes()->getByName('admin.content-formula.config');

        $this->assertNotNull($index);
        $this->assertNotNull($generate);
        $this->assertNotNull($config);

        $this->assertSame('admin/content-formula', $index->uri());
        $this->assertSame('admin/content-formula/generate', $generate->uri());
        $this->assertSame('admin/content-formula/config', $config->uri());

        $this->assertSame(ContentFormulaController::class . '@index', $index->getActionName());
        $this->assertSame(ContentFormulaController::class . '@generate', $generate->getActionName());
        $this->assertSame(ContentFormulaController::class . '@config', $config->getActionName());

        $this->assertContains('GET', $index->methods());
        $this->assertContains('HEAD', $index->methods());
        $this->assertSame(['POST'], $generate->methods());
        $this->assertContains('GET', $config->methods());
        $this->assertContains('HEAD', $config->methods());
    }
}
