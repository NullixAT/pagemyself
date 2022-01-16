<?php

namespace Framelix\Myself\View\Backend\Modules;

use Framelix\Framelix\Console;
use Framelix\Framelix\Form\Field\Select;
use Framelix\Framelix\Form\Field\Text;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\JsCall;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Session;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\Browser;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\HtmlUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\View\Backend\View;
use Framelix\Myself\ModuleHooks;
use Framelix\Myself\Storable\WebsiteSettings;

use function array_key_first;
use function array_search;
use function explode;
use function file_exists;
use function file_put_contents;
use function implode;
use function in_array;
use function is_dir;
use function scandir;
use function strtolower;
use function unlink;

use const FRAMELIX_APP_ROOT;

/**
 * Index
 */
class Index extends View
{

    /**
     * Access role
     * @var string|bool
     */
    protected string|bool $accessRole = "admin,content";

    /**
     * On js call
     * @param JsCall $jsCall
     */
    public static function onJsCall(JsCall $jsCall): void
    {
        switch ($jsCall->action) {
            case 'install':
                $tmpZip = __DIR__ . "/../../../../tmp/store-package-tmp.zip";
                if (file_exists($tmpZip)) {
                    unlink($tmpZip);
                }
                $browser = Browser::create();
                $browser->validateSsl = false;
                $browser->url = $jsCall->parameters['zipFile'];
                $browser->sendRequest();
                if ($browser->getResponseCode() === 200) {
                    file_put_contents($tmpZip, $browser->responseBody);
                    Console::$htmlOutput = true;
                    Buffer::start();
                    $status = Console::installZipPackage($tmpZip);
                    ModuleHooks::callHook('afterInstall', [], $jsCall->parameters['module']);
                    $output = Buffer::get();
                    Session::set('myself.modules.update.log', $output);
                    if (!$status) {
                        Toast::success('__myself_view_backend_modules_install_success__');
                    } else {
                        Toast::error('__myself_view_backend_modules_install_error__');
                    }
                }
                break;
            case 'enable':
            case 'disable':
                $module = $jsCall->parameters['module'];
                if (is_dir(FRAMELIX_APP_ROOT . "/modules/$module")) {
                    $modules = WebsiteSettings::get('enabledModules') ?? [];
                    $index = array_search($module, $modules);
                    if ($jsCall->action === 'disable') {
                        if ($index !== false) {
                            unset($modules[$index]);
                            Toast::success('__myself_view_backend_module_disabled__');
                        }
                    } else {
                        if ($index === false) {
                            $modules[] = $module;
                            Toast::success('__myself_view_backend_module_enabled__');
                        }
                    }
                    WebsiteSettings::set('enabledModules', $modules);
                }
                break;
            case 'store':
                $getBadgeHtml = function (
                    string $color,
                    string $category,
                    string $label,
                    ?string $tooltip = null
                ): string {
                    return '<div class="module-badge" style="background: ' . $color . '" data-filter-category="' . $category . '" title="' . $tooltip . '">'
                        . HtmlUtils::escape(Lang::get($label)) . '</div>';
                };
                $packageJsonRoot = JsonUtils::readFromFile(FRAMELIX_APP_ROOT . "/package.json");
                $currentMajorVersion = (int)explode(".", $packageJsonRoot['version'])[0];
                $browser = Browser::create();
                $browser->validateSsl = false;
                $browser->url = 'https://nullixat.github.io/pagemyself-module-store/modulelist.json';
                $browser->sendRequest();
                $moduleList = [];
                // get installed modules
                $folders = scandir(FRAMELIX_APP_ROOT . "/modules");
                foreach ($folders as $folder) {
                    $packageJson = FRAMELIX_APP_ROOT . "/modules/$folder/package.json";
                    if (file_exists($packageJson)) {
                        $moduleLower = strtolower($folder);
                        $packageJsonData = JsonUtils::readFromFile($packageJson);
                        $moduleData = JsonUtils::readFromFile($packageJson)['pagemyself'] ?? null;
                        if (!$moduleData) {
                            continue;
                        }
                        $moduleData['module'] = $folder;
                        $moduleData['version'] = $packageJsonData['version'];
                        if (isset($packageJsonData['homepage'])) {
                            $moduleData['homepage'] = $packageJsonData['homepage'];
                        }
                        $moduleData['lang'][Lang::$lang]['name'] = Lang::get(
                            '__' . $moduleLower . "_module_name__"
                        );
                        $moduleData['lang'][Lang::$lang]['info'] = Lang::get(
                            '__' . $moduleLower . "_module_info__"
                        );
                        $moduleList[$folder] = $moduleData;
                    }
                }
                $moduleList = ArrayUtils::merge($moduleList, JsonUtils::decode($browser->responseBody));
                foreach ($moduleList as $row) {
                    $firstLang = array_key_first($row['lang']);
                    $moduleFolder = FRAMELIX_APP_ROOT . "/modules/" . $row['module'];
                    $status = is_dir($moduleFolder) ? 'installed' : 'available';
                    $badges = [];
                    foreach ($row['categories'] as $category) {
                        $badges[] = $getBadgeHtml(
                            '#2a3746',
                            $category,
                            '__myself_view_backend_modules_category_' . $category . '__'
                        );
                    }
                    $packageJsonModule = null;
                    $incompatible = false;
                    if (($row['releaseId'] ?? null) && $currentMajorVersion < $row['minMajorVersion'] || $currentMajorVersion > $row['maxMajorVersion']) {
                        $incompatible = true;
                        $badges[] = $getBadgeHtml(
                            '#c2702d',
                            'incompatible_online',
                            '__myself_view_backend_modules_status_incompatible_online__',
                            '__myself_view_backend_modules_status_incompatible_online_info__'
                        );
                    }
                    if ($status === 'installed') {
                        $packageJsonModule = JsonUtils::readFromFile($moduleFolder . "/package.json");
                        if ($currentMajorVersion < $packageJsonModule['pagemyself']['minMajorVersion'] || $currentMajorVersion > $packageJsonModule['pagemyself']['maxMajorVersion']) {
                            $incompatible = true;
                            $badges[] = $getBadgeHtml(
                                '#c2702d',
                                'incompatible_local',
                                '__myself_view_backend_modules_status_incompatible_local__',
                                '__myself_view_backend_modules_status_incompatible_local_info__'
                            );
                        }
                        $badges[] = $getBadgeHtml(
                            '#2dc26b',
                            'installed',
                            Lang::get(
                                '__myself_view_backend_modules_status_installed__'
                            ) . ' ' . $packageJsonModule['version']
                        );
                        if ($packageJsonModule['version'] !== $row['version'] && ($row['releaseId'] ?? null) && !$incompatible) {
                            $status = 'updatable';
                            $badges[] = $getBadgeHtml(
                                'red',
                                'updatable',
                                '__myself_view_backend_modules_status_updatable__'
                            );
                        }
                        if (in_array($row['module'], WebsiteSettings::get('enabledModules') ?? [])) {
                            $badges[] = $getBadgeHtml(
                                '#2b312f',
                                'enabled',
                                '__myself_view_backend_modules_status_enabled__'
                            );
                        } else {
                            $badges[] = $getBadgeHtml(
                                '#2b312f',
                                'disabled',
                                '__myself_view_backend_modules_status_disabled__'
                            );
                        }
                    }
                    $zipFile = '';
                    if (!($row['releaseId'] ?? null)) {
                        $screenshotUrl = file_exists($moduleFolder . "/screenshot.png") ? Url::getUrlToFile(
                            $moduleFolder . "/screenshot.png"
                        ) : '';
                    } else {
                        $zipFile = 'https://nullixat.github.io/pagemyself-module-store/modules/' . $row['module'] . "/package.zip";
                        $screenshotUrl = 'https://nullixat.github.io/pagemyself-module-store/modules/' . $row['module'] . "/screenshot.png";
                    }
                    $info = HtmlUtils::escape(
                        $row['lang'][Lang::$lang]['info'] ?? $row['lang'][$firstLang]['info']
                    );
                    $links = [];
                    if (isset($row['homepage'])) {
                        $links[] = '<a href="' . $row['homepage'] . '" target="_blank" rel="nofollow">' . Lang::get(
                                '__myself_view_backend_modules_homepage__'
                            ) . '</a>';
                    }
                    if (isset($row['repository'])) {
                        $links[] = '<a href="https://github.com/' . $row['repository'] . '" target="_blank" rel="nofollow">' . Lang::get(
                                '__myself_view_backend_modules_repository__'
                            ) . '</a>';
                    }
                    ?>
                    <div class="module-entry"
                         data-screenshot-url="<?= $screenshotUrl ?>"
                         data-module="<?= $row['module'] ?>"
                         data-status="<?= $status ?>"
                         data-zip-file="<?= $zipFile ?>">
                        <div class="module-badges">
                            <?= implode('', $badges) ?>
                        </div>
                        <div class="module-entry-name"><?= HtmlUtils::escape(
                                $row['lang'][Lang::$lang]['name'] ?? $row['lang'][$firstLang]['name']
                            ) ?> v<?= $row['version'] ?></div>
                        <div class="module-entry-info"><?= $info ?></div>
                        <div class="module-entry-links"><?= implode(
                                '<div class="framelix-word-separator"></div>',
                                $links
                            ) ?></div>
                    </div>
                    <?php
                }
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
        echo '<div class="filters" style="display: flex; gap:10px; align-items: center">';
        echo '<span>Filter: </span>';

        $field = new Select();
        $field->name = "category";
        $field->addOption('all', null);
        $field->addOption('theme', null);
        $field->addOption('pageblock', null);
        $field->addOption('installed', null);
        $field->addOption('incompatible_online', null);
        $field->addOption('incompatible_local', null);
        $field->addOption('updatable', null);
        $field->addOption('disabled', null);
        $field->addOption('enabled', null);
        foreach ($field->getOptions() as $row) {
            $field->removeOption($row[0]);
            $field->addOption($row[0], '__myself_view_backend_modules_category_' . $row[0] . '__');
        }
        $field->defaultValue = 'all';
        $field->minWidth = 200;
        $field->showResetButton = false;
        $field->show();

        $field = new Text();
        $field->name = "search";
        $field->placeholder = 'Search...';
        $field->minWidth = 200;
        $field->show();

        echo '</div>';
        ?>
        <div class="framelix-spacer"></div>
        <div class="module-list">
            <div class="module-entries">
                <div class="framelix-loading"></div>
            </div>
            <div class="module-preview"></div>
        </div>
        <style>
          .filters {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 10px;
            background: #f5f5f5;
          }
          .filters .framelix-form-field {
            margin: 0;
            padding: 0;
          }
          .filters .framelix-form-field-container {
            margin: 0;
          }
          .module-list {
            display: flex;
          }
          .module-entries {
            flex: 1 1 auto;
          }
          .module-entry {
            margin-bottom: 5px;
            background: #f5f5f5;
            cursor: pointer;
            padding: 10px;
            border-radius: var(--border-radius);
          }
          .module-entry:hover,
          .module-entry-active {
            background: #e5e5e5;
          }
          .module-preview {
            min-width: 40vw;
          }
          .module-entry-name {
            font-weight: bold;
          }
          .module-badge {
            font-size: 0.8rem;
            border-radius: 50px;
            display: inline-flex;
            margin-right: 10px;
            padding: 0 10px;
            text-transform: uppercase;
            background: #2a3746;
            color: white;
            align-items: center;
          }
          .module-badge .material-icons {
            margin-right: 10px;
            position: relative;
            top: 1px;
          }
          .module-screenshot {
            min-height: 300px;
            max-height: 300px;
            padding: 10px;
            box-shadow: rgba(0, 0, 0, 0.1) 0 0 10px;
            margin-bottom: 10px;
            background: white no-repeat center;
            background-size: contain;
          }
        </style>
        <script>
          (async function () {

            function updateVisibility () {
              const entries = $('.module-entry')
              const filterSettings = FormDataJson.toJson(filters)
              entries.each(function () {
                let filterCategoriesAvailable = []
                $(this).find('[data-filter-category]').each(function () {
                  filterCategoriesAvailable.push($(this).attr('data-filter-category'))
                })
                let visible = true
                if (visible && filterSettings.search.trim().length) {
                  const searchableText = $(this).text() + '|' + $(this).attr('data-module')
                  visible = !!searchableText.match(new RegExp(FramelixStringUtils.escapeRegex(filterSettings.search.trim()), 'i'))
                }
                if (visible) {
                  if (filterSettings.category !== 'all' && filterCategoriesAvailable.indexOf(filterSettings.category) === -1) {
                    visible = false
                  }
                }
                $(this).toggleClass('hidden', !visible)
              })
            }

            async function install (module, zipFile) {
              if (await FramelixModal.confirm(FramelixLang.get('__myself_view_backend_modules_install_info__', ['<a href="' + zipFile + '" target="_blank">' + zipFile + '</a>'])).confirmed) {
                Framelix.showProgressBar(1)
                await FramelixApi.callPhpMethod(<?=JsonUtils::encode(
                    JsCall::getCallUrl(__CLASS__, 'install')
                )?>, { 'module': module, 'zipFile': zipFile })
                reloadPage(module)
              }
            }

            function reloadPage (module) {
              window.location.href = '<?=Url::getBrowserUrl()
                  ->removeParameter('module')->setParameter('module', '')?>' + module
            }

            const entriesContainer = $('.module-entries')
            const filters = $('.filters')
            /** @type {FramelixFormFieldText} */
            const fieldSearch = FramelixFormField.getFieldByName(filters, 'search')
            await fieldSearch.rendered
            fieldSearch.input.attr('type', 'search')
            fieldSearch.input.attr('data-continuous-search', '1')

            fieldSearch.container.on('search-start', function () {
              updateVisibility()
            })

            filters.on(FramelixFormField.EVENT_CHANGE_USER, function () {
              updateVisibility()
            })

            const response = await FramelixApi.callPhpMethod(<?=JsonUtils::encode(
                JsCall::getCallUrl(__CLASS__, 'store')
            )?>)
            const initialActiveModule = <?=JsonUtils::encode(Request::getGet('module'))?>;
            entriesContainer.html(response)
            updateVisibility()
            entriesContainer.on('click', '.module-entry', function (ev) {
              if ($(ev.target).is('a')) return
              const preview = $('.module-preview')
              preview.empty()
              const entry = $(this)
              let filterCategoriesAvailable = []
              entry.find('[data-filter-category]').each(function () {
                filterCategoriesAvailable.push($(this).attr('data-filter-category'))
              })
              const attrData = entry.data()
              $('.module-entry').toggleClass('module-entry-active', false)
              entry.addClass('module-entry-active')
              if (attrData.screenshotUrl) {
                preview.append($(`<div class="module-screenshot"></div>`).css('background-image', 'url(' + attrData.screenshotUrl + ')'))
              }
              switch (attrData.status) {
                case 'available':
                  preview.append($(`<button class="framelix-button framelix-button-block framelix-button-primary">${FramelixLang.get('__myself_view_backend_modules_button_install__')}</div>`).on('click', function () {
                    install(attrData.module, attrData.zipFile)
                  }))
                  break
                case 'updatable':
                  preview.append($(`<button class="framelix-button framelix-button-block framelix-button-primary">${FramelixLang.get('__myself_view_backend_modules_button_update__')}</div>`).on('click', function () {
                    install(attrData.module, attrData.zipFile)
                  }))
                  break
              }
              if (filterCategoriesAvailable.indexOf('enabled') > -1) {
                preview.append($(`<button class="framelix-button framelix-button-block framelix-button-primary">${FramelixLang.get('__myself_view_backend_modules_button_disable__')}</div>`).on('click', async function () {
                  await FramelixApi.callPhpMethod(<?=JsonUtils::encode(
                      JsCall::getCallUrl(__CLASS__, 'disable')
                  )?>, { 'module': attrData.module })
                  reloadPage(attrData.module)
                }))
              }
              if (filterCategoriesAvailable.indexOf('disabled') > -1) {
                preview.append($(`<button class="framelix-button framelix-button-block framelix-button-primary">${FramelixLang.get('__myself_view_backend_modules_button_enable__')}</div>`).on('click', async function () {
                  await FramelixApi.callPhpMethod(<?=JsonUtils::encode(
                      JsCall::getCallUrl(__CLASS__, 'enable')
                  )?>, { 'module': attrData.module })
                  // testing some page calls after enabling, if any returns an error we disable it instantly
                  const testUrls = ['<?=Url::getApplicationUrl()?>', '<?=Url::getBrowserUrl()?>']
                  for (let i = 0; i < testUrls.length; i++) {
                    const request = FramelixRequest.request('get', testUrls[i])
                    if (await request.checkHeaders() !== 0) {
                      await FramelixApi.callPhpMethod(<?=JsonUtils::encode(
                          JsCall::getCallUrl(__CLASS__, 'disable')
                      )?>, { 'module': attrData.module })
                      FramelixToast.error('__myself_view_backend_module_enable_error__')
                      return
                    }
                  }
                  reloadPage(attrData.module)
                }))
              }
            })
            if (initialActiveModule) {
              entriesContainer.find('.module-entry').filter('[data-module=\'' + initialActiveModule + '\']').trigger('click')
            }
          })()
        </script>
        <?php
    }
}