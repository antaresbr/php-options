<?php declare(strict_types=1);

use Antares\Support\Options;
use Antares\Support\Str;
use PHPUnit\Framework\TestCase;

final class OptionsTest extends TestCase
{
    private function getPrototypeArray()
    {
        return [
            'project' => ['types' => 'string'],
            'firstOption' => ['nullable' => true, 'default' => 'first', 'types' => 'string'],
            'secondOption' => ['types' => 'string'],
            'trueOption' => ['types' => 'boolean'],
            'falseOption' => ['types' => 'boolean'],
            'fruits' => ['types' => 'array'],
            'object' => ['types' => Options::class, 'nullable' => false],
        ];
    }

    private function getOptionsArray()
    {
        return [
            'project' => 'options',
            'secondOption' => 'second',
            'trueOption' => true,
            'falseOption' => false,
            'fruits' => ['apple', 'banana', 'mango', 'avocado', 'grape', 'cherries'],
            'object' => new Options(),
        ];
    }

    private function getWorkOptions()
    {
        return Options::make($this->getOptionsArray(), $this->getPrototypeArray());
    }

    public function testOptions_make_method()
    {
        $this->assertInstanceOf(Options::class, $this->getWorkOptions());
    }

    public function testOptions_offsetExists_method()
    {
        $this->assertTrue($this->getWorkOptions()->offsetExists('fruits'));
    }

    public function testOptions_offsetGet_method()
    {
        $oa = $this->getOptionsArray();

        $this->assertEquals($oa['project'], $this->getWorkOptions()->offsetGet('project'));
    }

    public function testOptions_offsetSet_method()
    {
        $wo = $this->getWorkOptions();

        $wo->offsetSet('newFruit', 'melon');
        $this->assertEquals('melon', $wo->offsetGet('newFruit'));
    }

    public function testOptions_offsetUnset_method()
    {
        $wo = $this->getWorkOptions();

        $wo->offsetSet('newFruit', 'melon');
        $this->assertEquals('melon', $wo->offsetGet('newFruit'));

        $wo->offsetUnset('newFruit');
        $this->assertEquals(null, $wo->offsetGet('newFruit'));
    }

    public function testOptions_count_method()
    {
        $this->assertEquals(count($this->getOptionsArray()), $this->getWorkOptions()->count());
    }

    public function testOptions_getIterator_method()
    {
        $this->assertInstanceOf(ArrayIterator::class, $this->getWorkOptions()->getIterator());
    }

    public function testOptions_jsonSerialize_method()
    {
        $this->assertJson(json_encode($this->getWorkOptions()->jsonSerialize()));
    }

    public function testOptions_reset_method()
    {
        $wo = $this->getWorkOptions();
        $wo->reset([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);
        $this->assertEquals(3, $wo->count());
    }

    public function testOptions_all_method()
    {
        $oa = $this->getOptionsArray();
        $wo = $this->getWorkOptions();

        $this->assertIsArray($wo->all());
        $this->assertEquals(count($oa), count($wo->all()));
    }

    public function testOptions_toArray_method()
    {
        $oa = $this->getOptionsArray();
        $wo = $this->getWorkOptions();

        $this->assertIsArray($wo->toArray());
        $this->assertEquals(count($oa), count($wo->toArray()));
    }

    public function testOptions_hash_method()
    {
        $hash = $this->getWorkOptions()->hash();

        $this->assertIsString($hash);
        $this->assertEquals(32, Str::length($hash));
    }

    public function testOptions_isEmpty_method()
    {
        $wo = $this->getWorkOptions();

        $this->assertFalse($wo->isEmpty());
        $wo->reset([]);
        $this->assertTrue($wo->isEmpty());
    }

    public function testOptions_has_method()
    {
        $wo = $this->getWorkOptions();

        $this->assertTrue($wo->has('fruits'));
        $this->assertFalse($wo->has('cars'));
    }

    public function testOptions_set_method()
    {
        $wo = $this->getWorkOptions();

        $wo->set('newFruit', 'melon');
        $this->assertTrue($wo->has('newFruit'));
        $this->assertEquals('melon', $wo->offsetGet('newFruit'));
    }

    public function testOptions_forget_method()
    {
        $wo = $this->getWorkOptions();

        $wo->set('newFruit', 'melon');
        $this->assertTrue($wo->has('newFruit'));

        $wo->forget('newFruit');
        $this->assertFalse($wo->has('newFruit'));
    }

    public function testOptions_get_method()
    {
        $oa = $this->getOptionsArray();
        $options = Options::make($oa, $this->getPrototypeArray());

        $this->assertEquals('first', $options->get('firstOption'));
        $this->assertEquals($oa['secondOption'], $options->get('secondOption'));
        $this->assertEquals($oa['trueOption'], $options->get('trueOption'));
        $this->assertEquals($oa['falseOption'], $options->get('falseOption'));
        $this->assertInstanceOf(Options::class, $options->get('object'));
    }

    public function testOptions_magical_get()
    {
        $oa = $this->getOptionsArray();
        $options = Options::make($oa, $this->getPrototypeArray());

        $this->assertEquals('first', $options->firstOption);
        $this->assertEquals($oa['secondOption'], $options->secondOption);
        $this->assertEquals($oa['trueOption'], $options->trueOption);
        $this->assertEquals($oa['falseOption'], $options->falseOption);
        $this->assertInstanceOf(Options::class, $options->object);
    }

    public function testOptions_isValidValue_method()
    {
        $wo = $this->getWorkOptions();

        $this->assertTrue($wo->isValidValue(true, 'boolean'));
        $this->assertTrue($wo->isValidValue(true, 'boolean|string|array'));
        $this->assertFalse($wo->isValidValue([], 'boolean|string'));
    }

    public function testOptions_getPrototypes_method()
    {
        $pa = $this->getPrototypeArray();
        $wo = $this->getWorkOptions();

        $this->assertIsArray($wo->getPrototypes());
        $this->assertEquals(count($pa), count($wo->getPrototypes()));
        $this->assertEquals($pa, $wo->getPrototypes());
    }

    public function testOptions_setPrototypes_method()
    {
        $wo = $this->getWorkOptions();
        $wo->setPrototypes([]);

        $this->assertIsArray($wo->getPrototypes());
        $this->assertEquals(0, count($wo->getPrototypes()));
    }

    public function testOptions_validate_method()
    {
        $wo = $this->getWorkOptions();

        $this->assertInstanceOf(Options::class, $wo->validate());
    }
}
