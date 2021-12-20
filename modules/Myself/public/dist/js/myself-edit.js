'use strict';
/**
 * Myself Edit Class
 */

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class MyselfEdit {
  /** @type Map<HTMLElement,Cash> */

  /**
   * Page block edit url
   * @type {string}
   */

  /**
   * Theme settings edit url
   * @type {string}
   */

  /**
   * Website settings url
   * @type {string}
   */

  /**
   * Url to open block editor
   */

  /**
   * Row settings edit url
   * @type {string}
   */

  /**
   * Column settings edit url
   * @type {string}
   */

  /**
   * Save settings  url
   * @type {string}
   */

  /**
   * Url to tinymce folder
   * @type {string}
   */

  /**
   * The current block layout config
   * @type {{}}
   */

  /**
   * Is block layout editor initialized
   * @type {boolean}
   */

  /**
   * Init late
   */
  static initLate() {
    MyselfEdit.editBlockMap = new Map();
    const editFrame = $('.myself-edit-frame-inner iframe');
    let editFrameWindow = editFrame[0].contentWindow;
    let editFrameDoc = $(editFrameWindow.document);
    const leftEditArea = $('.myself-edit-frame-outer-left').children('.myself-edit-frame-outer-margin');
    const rightEditArea = $('.myself-edit-frame-outer-right').children('.myself-edit-frame-outer-margin');
    let lastPositions = [];

    function step() {
      if (MyselfEdit.editBlockMap) {
        let i = 0;
        MyselfEdit.editBlockMap.forEach(function (editRow, srcElement) {
          if (typeof lastPositions[i] === 'undefined') {
            lastPositions.push(0);
          }

          const boundRectSrc = srcElement.getBoundingClientRect();

          if (lastPositions[i] !== boundRectSrc.top) {
            lastPositions[i] = boundRectSrc.top;
            editRow.css('top', boundRectSrc.top + 'px');
          }

          i++;
        });
      }

      window.requestAnimationFrame(step);
    }

    window.requestAnimationFrame(step);
    MyselfEdit.bindLiveEditableText(window);
    $('[data-help-text-id]').each(function () {});
    editFrame.on('load', function () {
      if (MyselfEdit.editBlockMap) {
        MyselfEdit.editBlockMap.forEach(function (editRow, srcElement) {
          editRow.remove();
        });
      }

      lastPositions = [];
      editFrameWindow = editFrame[0].contentWindow;
      editFrameDoc = $(editFrameWindow.document);
      editFrameDoc[0].querySelector('html').setAttribute('data-edit-frame', '1');
      let url = new URL(editFrameWindow.location.href);
      url.searchParams.set('editMode', '1');
      window.history.pushState(null, null, url);
      editFrameDoc.find('.myself-page-block').addClass('myself-page-block-editable').each(function () {
        const pageBlockId = this.getAttribute('data-id');
        const editRow = $('<div class="myself-edit-frame-button-row myself-edit-frame-button-row-float" data-page-block-id="' + pageBlockId + '"></div>');
        editRow.append(`<button class="framelix-button myself-page-api-call" data-action="edit" data-page-block-id="${pageBlockId}" data-icon-left="edit" title="__myself_pageblock_edit__"></button>`);
        const boundingRect = this.getBoundingClientRect();
        const middle = boundingRect.left + boundingRect.width / 2;

        if (middle <= window.innerWidth / 2) {
          leftEditArea.append(editRow);
        } else {
          rightEditArea.append(editRow);
        }

        MyselfEdit.editBlockMap.set(this, editRow);
        editFrameWindow.$(this).on('focusin mouseenter', function () {
          MyselfEdit.editBlockMap.forEach(function (innerEditRow, srcElement) {
            innerEditRow.removeClass('myself-edit-frame-button-row-active');
          });
          editRow.addClass('myself-edit-frame-button-row-active');
        });
        editRow[0].ignoreDomObserver = true;
      });
      MyselfEdit.bindLiveEditableText(editFrameWindow);
      MyselfEdit.bindLiveEditableWysiwyg(editFrameWindow);
    });
    $(document).on('mouseenter mouseleave', '.myself-edit-frame-button-row', function (ev) {
      let activeRow = ev.type !== 'mouseout' ? this : null;
      MyselfEdit.editBlockMap.forEach(function (editRow, srcElement) {
        srcElement.classList.toggle('myself-page-block-highlight', editRow[0] === activeRow);
      });
    });
    $(document).on('click', '.myself-website-settings', async function () {
      FramelixModal.hideAll();
      const modal = await FramelixModal.request('post', MyselfEdit.websiteSettingsEditUrl, null, null, false, null, true);
      modal.contentContainer.addClass('myself-edit-font');
      modal.closed.then(function () {
        location.reload();
      });
    });
    $(document).on('click', '.myself-theme-api-call', async function () {
      const urlParams = {
        'action': null
      };

      for (let k in urlParams) {
        urlParams[k] = this.dataset[k] || null;
      }

      FramelixModal.hideAll();
      const modal = await FramelixModal.request('post', MyselfEdit.themeSettingsEditUrl, urlParams, null, false, null, true);
      modal.contentContainer.addClass('myself-edit-font');
      modal.closed.then(function () {
        location.reload();
      });
    });
    $(document).on('click', '.myself-page-api-call', async function () {
      if (this.dataset.confirm && !(await FramelixModal.confirm('__sure__').closed).confirmed) return;
      const urlParams = {
        'action': null,
        'pageId': null,
        'pageBlockId': null,
        'pageBlockClass': null
      };

      for (let k in urlParams) {
        urlParams[k] = this.dataset[k] || null;
      }

      FramelixModal.hideAll();
      const modal = await FramelixModal.request('post', MyselfEdit.pageBlockEditUrl, urlParams, null, false, null, true);
      modal.contentContainer.addClass('myself-edit-font');
      modal.contentContainer.on(FramelixForm.EVENT_SUBMITTED, function () {
        editFrameWindow.location.reload();
      });
    });
    $(document).on('click', '.myself-open-layout-block-editor', function () {
      FramelixModal.callPhpMethod(MyselfEdit.blockLayoutEditorUrl, null, true);
    });
    $(document).on('change', '.myself-page-block-edit-tabs', function (ev) {
      const target = $(ev.target);
      const tabContent = target.closest('.framelix-tab-content');
      const tabButton = tabContent.closest('.framelix-tabs').children('.framelix-tab-buttons').children('.framelix-tab-button[data-id=\'' + tabContent.attr('data-id') + '\']');

      if (!tabButton.find('.myself-tab-edited').length) {
        tabButton.prepend('<span class="material-icons myself-tab-edited" title="__myself_unsaved_changes__">warning</span>');
      }
    });
    $(document).on(FramelixForm.EVENT_SUBMITTED, '.myself-page-block-edit-tabs', function (ev) {
      const target = $(ev.target);
      const tabContent = target.closest('.framelix-tab-content');
      const tabButton = tabContent.closest('.framelix-tabs').children('.framelix-tab-buttons').children('.framelix-tab-button[data-id=\'' + tabContent.attr('data-id') + '\']');
      tabButton.find('.myself-tab-edited').remove();
    });
    $(document).on('click', '.myself-block-layout-row [data-action]', function () {});
  }
  /**
   * Render block layout editor
   */


  static async renderBlockLayoutEditor() {
    if (!Framelix.hasObjectKeys(MyselfEdit.blockLayoutConfig)) {
      MyselfEdit.blockLayoutConfig = {};
    }

    if (!Framelix.hasObjectKeys(MyselfEdit.blockLayoutConfig.rows)) {
      MyselfEdit.blockLayoutConfig.rows = [];
    }

    const container = $('.myself-block-layout-editor');
    container.html('<div class="framelix-loading"></div>');
    container.empty(); // get all unassigned blocks and add it to the end of the list

    let unassignedPageBlocks = Object.assign({}, MyselfEdit.blockLayoutConfig.allPageBlocks);

    for (let i = 0; i < MyselfEdit.blockLayoutConfig.rows.length; i++) {
      const configRow = MyselfEdit.blockLayoutConfig.rows[i];

      for (let j = 0; j < configRow.columns.length; j++) {
        const configColumn = configRow.columns[j];

        if (configColumn.pageBlockId) {
          delete unassignedPageBlocks[configColumn.pageBlockId];
        }
      }
    }

    for (let pageBlockId in unassignedPageBlocks) {
      const pageBlockRow = unassignedPageBlocks[pageBlockId];
      MyselfEdit.blockLayoutConfig.rows.push({
        'columns': [{
          'title': pageBlockRow.title,
          'pageBlockId': pageBlockId,
          'settings': {}
        }]
      });
    }

    for (let i = 0; i < MyselfEdit.blockLayoutConfig.rows.length; i++) {
      var _configRow$settings, _configRow$settings2, _configRow$settings3;

      const configRow = MyselfEdit.blockLayoutConfig.rows[i];
      const row = $(`<div class="myself-block-layout-row"></div>`);
      const gap = (_configRow$settings = configRow.settings) === null || _configRow$settings === void 0 ? void 0 : _configRow$settings.gap;
      const maxWidth = (_configRow$settings2 = configRow.settings) === null || _configRow$settings2 === void 0 ? void 0 : _configRow$settings2.maxWidth;
      const align = (_configRow$settings3 = configRow.settings) === null || _configRow$settings3 === void 0 ? void 0 : _configRow$settings3.alignment;

      if (gap !== null && typeof gap !== 'undefined' && gap.length) {
        row.css('gap', gap + 'px');
      }

      if (maxWidth !== null && typeof maxWidth !== 'undefined' && maxWidth.length) {
        row.css('max-width', maxWidth + 'px');
      }

      if (align !== null && typeof align !== 'undefined' && align.length) {
        row.attr('data-align', align);
      }

      for (let j = 0; j < configRow.columns.length; j++) {
        const configColumn = configRow.columns[j];
        const empty = !configColumn.pageBlockId;
        row.append(`<div class="myself-block-layout-row-column" draggable="true" data-id="${j}" ${empty ? 'data-empty="1"' : ''}>
            <div class="myself-block-layout-row-column-title">
            ${FramelixLang.get(empty ? '__myself_blocklayout_empty__' : configColumn.title)}
            </div>
            <div class="myself-block-layout-row-column-actions">
              <button class="framelix-button framelix-button-primary" data-icon-left="settings"
                      title="__myself_blocklayout_settings_column__" data-action="settingscolumn"></button>
              <button class="framelix-button framelix-button-trans" data-icon-left="clear"
                      title="__myself_blocklayout_remove_column__" data-action="removecolumn"></button>
            </div>
        </div>`);
      }

      row.append(`<div class="myself-block-layout-row-column myself-block-layout-row-column-new">
            <div class="myself-block-layout-row-column-title">
            </div>
            <div class="myself-block-layout-row-column-actions">
              <button class="framelix-button framelix-button-primary" data-icon-left="settings"
                      title="__myself_blocklayout_settings_row__" data-action="settingsrow"></button>
              <button class="framelix-button framelix-button-success" data-icon-left="add"
                      title="__myself_blocklayout_add_column__" data-action="addcolumn"></button>
            </div>
        </div>`);
      row.attr('data-id', i);
      container.append(row);
    }

    container.append(`<div class="myself-block-layout-row framelix-responsive-grid-2">        
        <button class="myself-block-layout-row-column framelix-button framelix-button-success" data-icon-left="save" data-action="save">${FramelixLang.get('__myself_blocklayout_save__')}</button>
        <button class="myself-block-layout-row-column framelix-button framelix-button-primary" data-icon-left="add"
                        title="__myself_blocklayout_add_row__" data-action="addrow"></button>
    </div>`);

    if (!container.attr('data-initialized')) {
      container.attr('data-initialized', 1);
      container.on('click', '[data-action]', async function () {
        const row = $(this).closest('.myself-block-layout-row');
        const rowId = row.attr('data-id');
        const column = $(this).closest('.myself-block-layout-row-column');
        const columnId = column.attr('data-id');

        switch ($(this).attr('data-action')) {
          case 'save':
            {
              await FramelixApi.callPhpMethod(MyselfEdit.blockLayoutSaveSettingsUrl, {
                'rows': MyselfEdit.blockLayoutConfig.rows
              });
              location.reload();
            }
            break;

          case 'settingsrow':
            {
              let rowSettings = MyselfEdit.blockLayoutConfig.rows[rowId].settings;

              if (!rowSettings || Array.isArray(rowSettings)) {
                MyselfEdit.blockLayoutConfig.rows[rowId].settings = {};
                rowSettings = MyselfEdit.blockLayoutConfig.rows[rowId].settings;
              }

              const modal = await FramelixModal.callPhpMethod(MyselfEdit.blockLayoutRowSettingsEditUrl, {
                'settings': rowSettings
              }, true);
              modal.contentContainer.on('click', '.framelix-form-buttons [data-action=\'save\']', async function () {
                const form = FramelixForm.getById('rowsettings');
                if (!(await form.validate())) return;
                Object.assign(rowSettings, form.getValues());
                modal.close();
                MyselfEdit.renderBlockLayoutEditor();
              });
            }
            break;

          case 'settingscolumn':
            {
              let columnSettings = MyselfEdit.blockLayoutConfig.rows[rowId].columns[columnId].settings;

              if (!columnSettings || Array.isArray(columnSettings)) {
                MyselfEdit.blockLayoutConfig.rows[rowId].columns[columnId].settings = {};
                columnSettings = MyselfEdit.blockLayoutConfig.rows[rowId].columns[columnId].settings;
              }

              const modal = await FramelixModal.callPhpMethod(MyselfEdit.blockLayoutColumnSettingsEditUrl, {
                'settings': columnSettings
              }, true);
              modal.contentContainer.on('click', '.framelix-form-buttons [data-action=\'save\']', async function () {
                const form = FramelixForm.getById('columnsettings');
                if (!(await form.validate())) return;
                Object.assign(columnSettings, form.getValues());
                modal.close();
                MyselfEdit.renderBlockLayoutEditor();
              });
            }
            break;

          case 'addrow':
            MyselfEdit.blockLayoutConfig.rows.push({
              'columns': [{
                'title': ''
              }]
            });
            MyselfEdit.renderBlockLayoutEditor();
            break;

          case 'addcolumn':
            MyselfEdit.blockLayoutConfig.rows[rowId].columns.push({
              'title': ''
            });
            MyselfEdit.renderBlockLayoutEditor();
            break;

          case 'removecolumn':
            MyselfEdit.blockLayoutConfig.rows[rowId].columns.splice(columnId, 1);

            if (!MyselfEdit.blockLayoutConfig.rows[rowId].columns.length) {
              MyselfEdit.blockLayoutConfig.rows.splice(rowId, 1);
            }

            MyselfEdit.renderBlockLayoutEditor();
            break;
        }
      });
    }

    if (!MyselfEdit.blockLayoutEditorInitialized) {
      MyselfEdit.blockLayoutEditorInitialized = true;
      let dragEl = null;

      function swapColumns(columnA, columnB) {
        const columnIdA = columnA.attr('data-id');
        const columnIdB = columnB.attr('data-id');
        const rowA = columnA.closest('.myself-block-layout-row');
        const rowB = columnB.closest('.myself-block-layout-row');
        const rowIdA = rowA.attr('data-id');
        const rowIdB = rowB.attr('data-id');
        const configA = MyselfEdit.blockLayoutConfig.rows[rowIdA].columns[columnIdA];
        const configB = MyselfEdit.blockLayoutConfig.rows[rowIdB].columns[columnIdB];
        const settingsA = configA.settings;
        const settingsB = configB.settings;
        configB.settings = settingsA;
        configA.settings = settingsB;
        MyselfEdit.blockLayoutConfig.rows[rowIdA].columns[columnIdA] = configB;
        MyselfEdit.blockLayoutConfig.rows[rowIdB].columns[columnIdB] = configA;
        dragEl = null;
        MyselfEdit.renderBlockLayoutEditor();
      }

      $(document).on('dragstart', '.myself-block-layout-row-column[draggable]', function (ev) {
        dragEl = $(this);
        $('.myself-block-layout-row-column[draggable]').not(this).toggleClass('myself-block-layout-drop-highlight', true);
      });
      $(document).on('dragenter dragover', '.myself-block-layout-drop-highlight', function (ev) {
        $('.myself-block-layout-drop-highlight-strong').toggleClass('myself-block-layout-drop-highlight-strong', false);
        $(this).toggleClass('myself-block-layout-drop-highlight-strong', true);
        ev.preventDefault();
      });
      $(document).on('drop', '.myself-block-layout-drop-highlight', function (ev) {
        ev.preventDefault();

        if (dragEl) {
          swapColumns(dragEl, $(this));
        }
      });
      $(document).on('dragend', function (ev) {
        $('.myself-block-layout-drop-highlight').toggleClass('myself-block-layout-drop-highlight', false);
        dragEl = null;
      });
    }
  }
  /**
   * Bind live editable wysiwyg
   * @param {Window} frame
   */


  static async bindLiveEditableWysiwyg(frame) {
    const frameDoc = frame.document;
    const topFrame = frame.top;

    if (!frameDoc.myselfLiveEditableText) {
      frameDoc.myselfLiveEditableText = new Map();
    }

    const mediaBrowser = new MyselfFormFieldMediaBrowser();
    await frame.eval('FramelixDom').includeResource(MyselfEdit.tinymceUrl, 'tinymce');
    frame.eval('FramelixDom').addChangeListener('wysiwyg', async function () {
      $(frameDoc).find('.myself-live-editable-wysiwyg:not(.mce-content-body)').each(async function () {
        const container = frame.$(this);
        const originalContent = container.html();
        frame.tinymce.init({
          language: ['en', 'de'].indexOf(FramelixLang.lang) > -1 ? FramelixLang.lang : 'en',
          target: container[0],
          menubar: false,
          inline: true,
          plugins: 'image link media table hr advlist lists code',
          external_plugins: {
            myself: FramelixConfig.compiledFileUrls['Myself']['js']['tinymce']
          },
          file_picker_callback: async function file_picker_callback(callback, value, meta) {
            if (!mediaBrowser.signedGetBrowserUrl) {
              mediaBrowser.signedGetBrowserUrl = (await FramelixRequest.request('get', MyselfEdit.pageBlockEditUrl + '?action=getmediabrowserurl').getJson()).content;
            }

            await mediaBrowser.render();
            mediaBrowser.openBrowserBtn.trigger('click');
            mediaBrowser.modal.closed.then(function () {
              let url = null;

              if (!mediaBrowser.getValue()) {
                callback('');
                return;
              }

              const entry = mediaBrowser.selectedEntriesContainer.children().first();
              url = entry.attr('data-url');
              url = url.replace(/\?t=[0-9]+/g, '');
              callback(url);
            });
          },
          toolbar: 'myself-save-text myself-cancel-text | undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist checklist | table | forecolor backcolor removeformat | image media pageembed link | code',
          powerpaste_word_import: 'clean',
          powerpaste_html_import: 'clean',
          setup: function setup(editor) {
            editor.myself = {
              'container': container,
              'originalContent': originalContent,
              'pageBlockEditUrl': topFrame.eval('MyselfEdit').pageBlockEditUrl
            };
          }
        });
      });
    });
  }
  /**
   * Bind live editable text
   * @param {Window} frame
   */


  static bindLiveEditableText(frame) {
    const frameDoc = frame.document;
    const topFrame = frame.top;

    if (!frameDoc.myselfLiveEditableText) {
      frameDoc.myselfLiveEditableText = new Map();
    }

    const configMap = frameDoc.myselfLiveEditableText;
    $(frameDoc).on('focusin', '.myself-live-editable-text', function () {
      let config = configMap.get(this);

      if (config) {
        return;
      }

      config = {};
      configMap.set(this, config);
      const container = frame.$(this);
      const originalContent = container[0].innerText;
      config.saveBtn = frame.$(`<button class="framelix-button framelix-button-success framelix-button-small myself-editable-text-save-button" data-icon-left="save" title="__save__"></button>`);
      config.saveBtn.on('click', async function () {
        Framelix.showProgressBar(-1);
        await FramelixRequest.request('post', topFrame.eval('MyselfEdit').pageBlockEditUrl, {
          'action': 'save-editable-content'
        }, {
          'storableId': container.attr('data-id'),
          'propertyName': container.attr('data-property-name'),
          'arrayKey': container.attr('data-array-key'),
          'content': container[0].innerText
        });
        Framelix.showProgressBar(null);
        FramelixToast.success('__saved__');
      });
      config.cancelBtn = frame.$(`<button class="framelix-button framelix-button-small myself-editable-text-cancel-button" title="__cancel__" data-icon-left="clear"></button>`);
      config.cancelBtn.on('click', async function () {
        container[0].innerText = originalContent;
      });
      config.popup = frame.eval('FramelixPopup').showPopup(container, frame.$('<div>').append(config.saveBtn).append(config.cancelBtn), {
        closeMethods: 'manual'
      });
    }).on('change input blur paste', '.myself-live-editable-text', function (ev) {
      ev.stopPropagation();
      let config = configMap.get(this);

      if (!config) {
        return;
      }

      const container = $(this); // remove all styles and replace not supported elements

      if (container.attr('data-multiline') !== '1') {
        const newText = this.innerText.replace(/[\r\n]/g, '');

        if (newText !== this.innerText) {
          frame.eval('FramelixToast').error('__myself_storable_liveedit_nomultiline__');
          this.innerText = this.innerText.replace(/[\r\n]/g, '');
        }
      }

      if (ev.type === 'focusout' || ev.type === 'blur') {
        setTimeout(function () {
          config.popup.destroy();
          configMap.delete(container[0]);
        }, 100);
      }
    });
  }

}

_defineProperty(MyselfEdit, "editBlockMap", void 0);

_defineProperty(MyselfEdit, "pageBlockEditUrl", void 0);

_defineProperty(MyselfEdit, "themeSettingsEditUrl", void 0);

_defineProperty(MyselfEdit, "websiteSettingsEditUrl", void 0);

_defineProperty(MyselfEdit, "blockLayoutEditorUrl", void 0);

_defineProperty(MyselfEdit, "blockLayoutRowSettingsEditUrl", void 0);

_defineProperty(MyselfEdit, "blockLayoutColumnSettingsEditUrl", void 0);

_defineProperty(MyselfEdit, "blockLayoutSaveSettingsUrl", void 0);

_defineProperty(MyselfEdit, "tinymceUrl", void 0);

_defineProperty(MyselfEdit, "blockLayoutConfig", {});

_defineProperty(MyselfEdit, "blockLayoutEditorInitialized", false);

FramelixInit.late.push(MyselfEdit.initLate);