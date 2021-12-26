<?php

namespace Framelix\Myself\View\Backend\Nav;

use Exception;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;
use Framelix\Myself\Storable\Nav;

use function htmlentities;

/**
 * Arrange
 */
class Arrange extends View
{
    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,nav";

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'save':
                $storeRecursive = function (array $navData, ?Nav $parent) use (&$storeRecursive) {
                    $sort = 0;
                    foreach ($navData as $row) {
                        $nav = Nav::getById($row['id']);
                        if ($nav) {
                            $nav->parent = $parent;
                            $nav->sort = $sort++;
                            $nav->preserveUpdateUserAndTime();
                            $nav->store();
                            if (isset($row['childs'])) {
                                $storeRecursive($row['childs'], $nav);
                            }
                        }
                    }
                };
                $storeRecursive($jsCall->parameters['navData'] ?? [], null);
                Toast::success('__framelix_saved__');
                Url::getBrowserUrl()->redirect();
                break;
        }
    }

    /**
     * On request
     */
    public function onRequest(): void
    {
        $this->showContentBasedOnRequestType();
    }

    /**
     * Show content
     */
    public function showContent(): void
    {
        $navs = Nav::getByCondition('parent IS NULL', sort: ["+sort", "+title"]);
        $navData = [];
        $navDataFlat = [];
        foreach ($navs as $nav) {
            $this->addNavData($navData, $navDataFlat, $nav);
        }
        ?>
        <span class="framelix-button framelix-button-primary framelix-button-small"
              data-icon-left="import_export"><?= Lang::get('__myself_view_backend_nav_arrange_drag_sort__') ?></span>
        <span class="framelix-button framelix-button-warning framelix-button-small"
              data-icon-left="open_with"><?= Lang::get('__myself_view_backend_nav_arrange_drag_move__') ?></span>
        <div class="framelix-spacer"></div>
        <div class="nav-entries"></div>
        <div class="nav-entry-new-child-drop"><?= Lang::get('__myself_view_backend_nav_arrange_drop_main__') ?></div>
        <div class="hidden save-buttons">
            <button class="framelix-button framelix-button-success"
                    data-icon-left="save"><?= Lang::get('__framelix_save__') ?></button>
            <a href="<?= Url::getBrowserUrl() ?>" class="framelix-button"
               data-icon-left="clear"><?= Lang::get('__framelix_cancel__') ?></a>
        </div>
        <style>
          .nav-entries {
            padding: 5px 0;
          }
          .nav-entry {
            margin-bottom: 3px;
            background: var(--color-subtle-bg);
            padding: 2px 0;
            border-left: 15px solid var(--color-strong-bg);
          }
          .nav-entry[data-level='0'] {
            border-left-color: rgba(0, 0, 0, 0.1);
          }
          .nav-entry[data-level='1'] {
            border-left-color: rgba(0, 0, 0, 0.2);
          }
          .nav-entry[data-level='2'] {
            border-left-color: rgba(0, 0, 0, 0.3);
          }
          .nav-entry-title {
            display: flex;
            align-items: center;
            margin-left: 5px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
          }
          .nav-entry-buttons {
            flex: 0;
            white-space: nowrap;
          }
          .nav-entry-buttons .framelix-button {
            margin: 0;
          }
          .nav-entry-label {
            margin: 0 10px;
            flex: 1 1 40%;
          }
          .nav-entry-new-child-drop {
            min-width: 100px;
            font-size: 0.9rem;
            background: hsla(var(--color-success-hue), 100%, 50%, 0.1);
            padding: 5px 10px;
            display: flex;
            align-items: center;
            opacity: 0;
            margin-right: 10px;
          }
          .nav-entry-new-child-drop .material-icons {
            margin-right: 10px;
          }
          .dragging .nav-entry-drop-highlight {
            opacity: 1;
            box-shadow: var(--color-success-bg-strong) 0 0 20px, var(--color-success-bg-strong) 0 0 20px;
          }
          .nav-entry-level-warning {
            font-size: 0.9rem;
            opacity: 0.8;
          }
          .save-buttons {
            padding: 10px;
            position: sticky;
            bottom: 0;
            background: var(--color-page-bg);
            margin-top: 10px;
          }
        </style>
        <script>
          (function () {

            function getNavData () {
              let data = []
              fillNewDataRecursive(startContainer.children('.nav-entry'), data)
              fillNewDataRecursive(topLevelDrop.children('.nav-entry'), data)
              return data
            }

            function fillNewDataRecursive (entries, navData) {
              entries.each(function () {
                let id = $(this).attr('data-id')
                let row = navDataFlat[id]
                row.childs = []
                navData.push(row)
                fillNewDataRecursive($(this).children('.nav-entry-childs').children().children('.nav-entry'), row.childs)
                fillNewDataRecursive($(this).children('.nav-entry-title').children('.nav-entry-new-child-drop').children('.nav-entry'), row.childs)
              })
            }

            function build (container, rows, level) {
              level = level || 0
              const newContainer = $(`<div>`)
              for (let i = 0; i < rows.length; i++) {
                const row = rows[i]
                const navEntry = $(`<div class="nav-entry" data-id="${row.id}" data-level="${level}">
                        <div class="nav-entry-title">
                            <div class="nav-entry-buttons">
                                <span class="framelix-button framelix-button-primary framelix-button-small sort-handler"
                                      draggable="true" data-icon-left="import_export"></span>
                                <span class="framelix-button framelix-button-warning framelix-button-small drag-handler"
                                      draggable="true" data-icon-left="open_with"></span>
                            </div>
                            <div class="nav-entry-label">
                                ${row.flagDraft ? '<span class="myself-tag">' + FramelixLang.get('__myself_storable_nav_flagdraft_label__') + '</span>' : ''}
                                ${row.url === '' ? '<span class="myself-tag">Homepage</span>' : ''}
                                ${row.title}
                                ${level > 1 ? '<div class="nav-entry-level-warning">' + FramelixLang.get('__myself_view_backend_nav_arrange_many_levels__') + '</div>' : ''}
                            </div>
                            <div class="nav-entry-new-child-drop">
                                <span class="material-icons">download</span> ${FramelixLang.get('__myself_view_backend_nav_arrange_drop_child__')}
                            </div>
                        </div>
                        <div class="nav-entry-childs"></div>
                    </div>`)
                newContainer.append(navEntry)
                if (row.childs.length) {
                  const childContainer = $(`<div class="nav-entries"></div>`)
                  navEntry.children('.nav-entry-childs').append(childContainer)
                  build(childContainer, row.childs, level + 1)
                }
              }
              container.html(newContainer.html())
              topLevelDrop.find('.nav-entry').remove()
              if (!level) {
                $('.sort-handler').closest('.nav-entry').parent().each(function () {
                  new Sortable(this, {
                    'handle': '.sort-handler',
                    'onSort': function () {
                      saveButtons.removeClass('hidden')
                    }
                  })
                })
              }
            }

            let dragEl = null
            let navDataInitial = <?=JsonUtils::encode($navData)?>;
            let navDataFlat = <?=JsonUtils::encode($navDataFlat)?>;
            let startContainer = $('.nav-entries')
            let topLevelDrop = $('.nav-entry-new-child-drop')
            const saveButtons = $('.save-buttons')
            $(document).on('dragstart', '.drag-handler', function (ev) {
              dragEl = $(this).closest('.nav-entry')
              $('.nav-entry-drop-highlight').removeClass('nav-entry-drop-highlight')
              let dropzones = $('.nav-entry-new-child-drop')
                .not(dragEl.find('.nav-entry-new-child-drop'))
                .not(dragEl.parent().closest('.nav-entry').children('.nav-entry-title').children('.nav-entry-new-child-drop'))
              if (dragEl.attr('data-level') === '0') {
                dropzones = dropzones.not(topLevelDrop)
              }
              dropzones.toggleClass('nav-entry-drop-highlight', true)
            })
            $(document).on('dragend', function (ev) {
              $('.nav-entry-new-child-drop').toggleClass('nav-entry-drop-highlight', false)
            })
            $(document).on('dragenter dragover', '.nav-entry-drop-highlight', function (ev) {
              ev.preventDefault()
            })
            $(document).on('drop', '.nav-entry-drop-highlight', function (ev) {
              $('.nav-entry-drop-highlight').removeClass('nav-entry-drop-highlight')
              if (dragEl) {
                ev.preventDefault()
                $(this).append(dragEl)
                let data = getNavData()
                build(startContainer, data)
                saveButtons.removeClass('hidden')
              }
            })
            saveButtons.on('click', 'button', function () {
              FramelixApi.callPhpMethod('<?=JsCall::getCallUrl(
                  __CLASS__,
                  "save"
              )?>', { 'navData': getNavData() })
            })
            FramelixDom.includeCompiledFile('Framelix', 'js', 'sortablejs', 'Sortable').then(function () {
              build(startContainer, navDataInitial)
            })
          })()
        </script>
        <?
    }

    /**
     * Add nav data to internal array
     * @param array $navData
     * @param array $navDataFlat
     * @param Nav $nav
     * @throws Exception
     */
    private function addNavData(array &$navData, array &$navDataFlat, Nav $nav): void
    {
        $row = [
            'id' => $nav->id,
            'title' => htmlentities($nav->getLabel()),
            'flagDraft' => $nav->flagDraft,
            'url' => $nav->page->url ?? null,
            'childs' => []
        ];
        $navDataFlat[$nav->id] = $row;
        $childs = Nav::getByCondition('parent = {0}', [$nav], ["+sort", "+title"]);
        foreach ($childs as $child) {
            $this->addNavData($row['childs'], $navDataFlat, $child);
        }
        $navData[] = $row;
    }
}