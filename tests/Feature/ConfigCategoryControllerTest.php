<?php

namespace Tests\Feature;

use App\Models\Config;
use App\Models\ConfigCategory;
use Tests\AdminTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\RequestActions;

class ConfigCategoryControllerTest extends AdminTestCase
{
    use RefreshDatabase;
    use RequestActions;
    protected $resourceName = 'config-categories';

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testStoreValidation()
    {
        // slug 和 name 验证规则一样，验证一个即可

        // name required
        $res = $this->storeResource([
            'name' => '',
        ]);
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // name string
        $res = $this->storeResource([
            'name' => [],
        ]);
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // name max:50
        $res = $this->storeResource([
            'name' => str_repeat('a', 51),
        ]);
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        factory(ConfigCategory::class)->create(['name' => 'name']);
        // name unique
        $res = $this->storeResource([
            'name' => 'name',
        ]);
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function testStore()
    {
        $res = $this->storeResource([
            'name' => 'name',
            'slug' => 'slug',
        ]);
        $res->assertStatus(201);
        $this->assertDatabaseHas('config_categories', [
            'id' => 1,
            'name' => 'name',
            'slug' => 'slug',
        ]);
    }

    public function testUpdate()
    {
        factory(ConfigCategory::class)->create(['name' => 'name']);
        $res = $this->updateResource(1, [
            'name' => 'name',
        ]);
        $res->assertStatus(201);

        $res = $this->updateResource(1, [
            'name' => 'new',
            'slug' => 'new',
        ]);
        $res->assertStatus(201);
        $this->assertDatabaseHas('config_categories', [
            'id' => 1,
            'name' => 'new',
            'slug' => 'new',
        ]);
    }

    public function testDestroy()
    {
        factory(ConfigCategory::class, 2)->create();
        ConfigCategory::find(2)->configs()->createMany([factory(Config::class)->make()->toArray()]);

        $res = $this->destroyResource(1);
        $res->assertStatus(204);
        $this->assertDatabaseMissing('config_categories', [
            'id' => 1,
        ]);
        $this->assertDatabaseHas('configs', [
            'id' => 1,
            'category_id' => 2,
        ]);

        // 关联删除
        $res = $this->destroyResource(2);
        $res->assertStatus(204);
        $this->assertDatabaseMissing('config_categories', [
            'id' => 2,
        ]);
        $this->assertDatabaseMissing('configs', [
            'id' => 1,
        ]);
    }

    public function testIndex()
    {
        ConfigCategory::insert(factory(ConfigCategory::class, 20)->make()->toArray());
        ConfigCategory::find(1)->update(['name' => 'test query name']);
        ConfigCategory::find(2)->update(['name' => 'test query name 2']);

        $res = $this->getResources();
        $res->assertStatus(200)
            ->assertJsonCount(15, 'data');

        // name like %?%
        $res = $this->getResources([
            'name' => 'query',
        ]);
        $res->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    protected function storeConfig($data = [], $cateId = 1)
    {
        return $this->storeResource(
            $data,
            $this->resourceName.'.configs',
            ['id' => $cateId]
        );
    }

    public function testStoreConfigValidation()
    {
        factory(ConfigCategory::class)->create();
        factory(Config::class)->create([
            'name' => 'name',
            'slug' => 'slug',
        ]);

        // type, name, slug required
        // desc, options, value, validation_rules string
        $res = $this->storeConfig([
            'desc' => [],
            'options' => [],
            'value' => [],
            'validation_rules' => [],
        ]);
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'name', 'slug', 'desc', 'options', 'value', 'validation_rules']);

        // type in
        // name, slug string
        // desc, options, value, validation_rules max:xx
        $res = $this->storeConfig([
            'type' => 'not in',
            'name' => [],
            'slug' => [],
            'desc' => str_repeat('a', 256),
            'options' => str_repeat('a', 256),
            'value' => str_repeat('a', 5001),
            'validation_rules' => str_repeat('a', 256),
        ]);
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'name', 'slug', 'desc', 'options', 'value', 'validation_rules']);

        // name, slug max:50
        $res = $this->storeConfig([
            'type' => Config::TYPE_INPUT,
            'name' => str_repeat('a', 51),
            'slug' => str_repeat('a', 51),
        ]);
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug']);

        // name, slug unique
        $res = $this->storeConfig([
            'type' => Config::TYPE_INPUT,
            'name' => 'name',
            'slug' => 'slug',
        ]);
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function testStoreConfig()
    {
        factory(ConfigCategory::class)->create();

        $res = $this->storeConfig(factory(Config::class)->make()->toArray());
        $res->assertStatus(201);

        $this->assertDatabaseHas('configs', [
            'id' => 1,
            'category_id' => 1,
        ]);
    }
}
