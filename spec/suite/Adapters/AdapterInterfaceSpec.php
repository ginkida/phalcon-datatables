<?php
namespace Spec\Adapters;

use kahlan\plugin\Stub;
use DataTables\Adapters\AdapterInterface;
use DataTables\ParamsParser;

class MyAdapter extends AdapterInterface{

  public function getResponse() {
    return [];
  }

}


describe("AdapterInterface", function() {

  describe("->setParser()", function() {

    it("should not set parser if it not an instance of ParamsParser", function() {

      $adapter = new MyAdapter(10);
      expect(function() use($adapter) {
        $adapter->setParser([1, 2, 3]);
      })->toThrow();

    });

    it("should set parser successfully", function() {

      $adapter = new MyAdapter(10);
      $parser  = new ParamsParser(10);
      $adapter->setParser($parser);
      expect($adapter->getParser())->toBe($parser);

    });

  });

  describe("->setColumns()", function() {

    it("should throw when columns are not array", function() {

      $adapter = new MyAdapter(10);
      expect(function() use($adapter) {
        $adapter->setColumns('user, email, name');
      })->toThrow();

    });

    it("should successfully set columns", function() {

      $adapter = new MyAdapter(10);
      $columns  = ['user', 'name', 'c.email'];
      $adapter->setColumns($columns);
      expect($adapter->getColumns())->toBe([['user'], ['name'], ['c.email', 'alias' => 'email']]);

    });

  });

  it("->columnExists()", function() {

    $adapter = new MyAdapter(10);
    $columns  = ['user', 'name', 'email', 'r.role as role', ['c.id', 'alias' => 'id']];
    $adapter->setColumns($columns);
    expect($adapter->columnExists('user'))->toBe('user');
    expect($adapter->columnExists('id'))->toBe('c.id');
    expect($adapter->columnExists('id', true))->toBe('id');
    expect($adapter->columnExists('c.id'))->toBe('c.id');
    expect($adapter->columnExists('c.id', true))->toBe('id');
    expect($adapter->columnExists('role', true))->toBe('role');
    expect($adapter->columnExists('r.role'))->toBe('r.role');
    expect($adapter->columnExists('c.role'))->toBe(null);
    expect($adapter->columnExists('user1'))->toBe(null);

  });

  describe("->formResponse()", function() {

    beforeEach(function() {

      $adapter = new MyAdapter(10);
      $parser  = new ParamsParser(10);
      $adapter->setParser($parser);

      $this->adapter = $adapter;

    });

    it("should set default params", function() {

      $response = $this->adapter->formResponse([]);
      expect($response)->toBe([
        'draw' => null,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
      ]);

    });

  });

  it("->sanitaze()", function() {

    $string = str_repeat('*', 100);
    $adapter = new MyAdapter(10);
    $newString = $adapter->sanitaze($string);
    expect(strlen($newString))->toBe(10);

  });

  describe("->bind()", function() {

    it("should throw an exception on unknown bind", function() {

      expect(function() {
        $adapter = new MyAdapter(10);
        $adapter->bind('some_unknown_action', function() {
          echo 'Hello!';
        });
      })->toThrow();

    });

  });


});
