<?php

return array(
{foreach from=$table.fields item=field}

{if $field.uniqueName}
  '{$field.uniqueName}'
{else}
                                            '{$field.name}'
{/if}
               => array(
                 'name'      => '{$field.name}',
                                                                      'type'      => {$field.crmType},
{if $field.title}
                                                                      'title'     => {$tsFunctionName}('{$field.title}'),
{/if}
{if $field.comment}
                                                                      'description'     => {$tsFunctionName}('{$field.comment|replace:"'":"\'"}'),
{/if}
{if $field.required}
                                        'required'  => {$field.required|strtoupper},
{/if} {* field.required *}
{if isset($field.length)}
                      'maxlength' => {$field.length},
{/if} {* field.length *}
{if isset($field.precision)}
                      'precision'      => array({$field.precision}),
{/if}
{if isset($field.size)}
                      'size'      => {$field.size},
{/if} {* field.size *}
{if isset($field.rows)}
                      'rows'      => {$field.rows},
{/if} {* field.rows *}
{if isset($field.cols)}
                      'cols'      => {$field.cols},
{/if} {* field.cols *}

{if $field.import}
                      'import'    => {$field.import|strtoupper},

{/if} {* field.import *}
  'where'     => '{$table.name}.{$field.name}',
  {if $field.headerPattern}'headerPattern' => '{$field.headerPattern}',{/if}
  {if $field.dataPattern}'dataPattern' => '{$field.dataPattern}',{/if}
{if $field.export}
                      'export'    => {$field.export|strtoupper},
{/if} {* field.export *}
{if $field.contactType}
                      'contactType' => {if $field.contactType == 'null'}NULL{else}'{$field.contactType}'{/if},
{/if}
{if $field.rule}
                      'rule'      => '{$field.rule}',
{/if} {* field.rule *}
{if !empty($field.permission)}
                      'permission'      => {$field.permission|@print_array},
{/if}
{if $field.default || $field.default === '0'}
  {capture assign=unquotedDefault}{if ($field.default[0]=="'" or $field.default[0]=='"')}{$field.default|substring:1:-1}{else}{$field.default}{/if}{/capture}
                         'default'   => {if ($unquotedDefault==='NULL')}NULL{else}'{$unquotedDefault}'{/if},
{/if} {* field.default *}
  'table_name' => '{$table.name}',
  'entity' => '{$table.entity}',
  'bao' => '{$table.bao}',
  'localizable' => {if $field.localizable}1{else}0{/if},
  {if isset($field.localize_context)}'localize_context' => '{$field.localize_context}',{/if}

{if isset($field.FKClassName)}
                      'FKClassName' => '{$field.FKClassName}',
{/if}
{if !empty($field.component)}
                      'component' => '{$field.component}',
{/if}
{if $field.serialize}
  'serialize' => CRM_Core_DAO::SERIALIZE_{$field.serialize|strtoupper},
{/if}
{if $field.uniqueTitle}
  'unique_title' => {$tsFunctionName}('{$field.uniqueTitle}'),
{/if}
{if $field.deprecated}
  'deprecated' => TRUE,
{/if}
{if $field.html}
  'html' => array(
  {foreach from=$field.html item=val key=key}
    '{$key}' => {if $key eq 'label'}{$tsFunctionName}("{$val}"){elseif is_array($val)}{$val|@print_array}{else}'{$val}'{/if},
  {/foreach}
  ),
{/if}
{if $field.pseudoconstant}
  'pseudoconstant' => {$field.pseudoconstant|@print_array},
{/if}
{if $field.readonly || $field.name === $table.primaryKey.name}
  'readonly' => TRUE,
{/if}
  'add' => {if $field.add}'{$field.add}'{else}NULL{/if},
),
{/foreach} {* table.fields *}
];
