<?php

namespace ActiveRecord\Relationship;

use ActiveRecord\Inflector;
use ActiveRecord\Model;
use ActiveRecord\Relation;
use ActiveRecord\Table;
use ActiveRecord\Types;
use ActiveRecord\Utils;

/**
 * @todo implement me
 *
 * @template TModel of Model
 *
 * @phpstan-import-type HasAndBelongsToManyOptions from Types
 */
class HasAndBelongsToMany extends AbstractRelationship
{
    protected string $association_foreign_key = '';

    /**
     * @param HasAndBelongsToManyOptions $options
     */
    public function __construct(string $attribute, array $options = [])
    {
        parent::__construct($attribute, $options);

        if (!isset($this->class_name)) {
            $this->set_class_name($this->inferred_class_name(Utils::singularize($attribute)));
        }

        $this->options['association_foreign_key'] ??= Inflector::keyify($this->class_name);
    }

    public function is_poly(): bool
    {
        return true;
    }

    /**
     * @return list<TModel>
     */
    public function load(Model $model): mixed
    {
        /**
         * @var Relation<TModel>
         */
        $rel = new Relation($this->class_name, [], []);
        $rel->from($this->get_table()->table);
        $other_table = Table::load(get_class($model));
        $other_table_name = $other_table->table;
        $other_table_primary_key = $other_table->pk[0];
        $rel->where($other_table_name . '.' . $other_table_primary_key . ' = ?', $model->{$model->get_primary_key()});
        $rel->joins([get_class($model)]);

        return $rel->to_a();
    }

    public static function inferJoiningTableName(string $class_name, string $association_name): string
    {
        $parts = [$association_name, $class_name];
        sort($parts);

        return implode('_', $parts);
    }

    public function construct_inner_join_sql(Table $from_table, bool $using_through = false, ?string $alias = null): string
    {
        $other_table = Table::load($this->class_name);
        $associated_table_name = $other_table->table;
        $from_table_name = $from_table->table;
        $foreign_key = $this->options['foreign_key'];
        $join_primary_key = $this->options['association_foreign_key'];
        $linkingTableName = $this->options['join_table'];

        $from_table_primary_key = $from_table->pk[0];
        $associated_table_primary_key = $other_table->pk[0];

        $res = 'INNER JOIN ' . $linkingTableName . " ON ($from_table_name.$from_table_primary_key = " . $linkingTableName . ".$foreign_key) "
            . 'INNER JOIN ' . $associated_table_name . ' ON ' . $associated_table_name . '.' . $associated_table_primary_key . ' = ' . $linkingTableName . '.' . $join_primary_key;

        return $res;
    }

    public function load_eagerly($models, $attributes, $includes, Table $table): void
    {
        throw new \Exception('load_eagerly undefined for ' . __CLASS__);
    }

    public function is_string_this_relationship(string $other): bool
    {
        return parent::is_string_this_relationship($other) || $other === $this->class_name;
    }
}
