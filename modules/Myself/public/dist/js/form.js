'use strict';
/**
 * Ace editor
 */

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

class MyselfFormFieldAce extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "maxWidth", '100%');

    _defineProperty(this, "input", void 0);

    _defineProperty(this, "editor", void 0);

    _defineProperty(this, "mode", void 0);

    _defineProperty(this, "initialHidden", false);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue(value, isUserChange = false) {
    this.editor.setValue(this.stringifyValue(value));
    this.input.val(this.editor.getValue());
    this.triggerChange(this.field, isUserChange);
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    await super.renderInternal();
    await FramelixDom.includeResource(FramelixConfig.modulePublicUrl + '/vendor/ace-editor/ace.js', 'ace');
    await FramelixDom.includeResource(FramelixConfig.modulePublicUrl + '/vendor/ace-editor/ext-language_tools.js');
    const self = this;

    if (this.initialHidden) {
      const btn = $(`<button class="framelix-button framelix-button-primary">${FramelixLang.get('__myself_form_field_ace_activate__')}</button>`);
      self.field.append(btn);
      btn.on('click', function () {
        btn.next().removeClass('hidden');
        setTimeout(function () {
          btn.remove();
          self.editor.focus();
        }, 10);
      });
    }

    self.field.append(`<div class="myself-form-field-ace-editor ${this.initialHidden ? 'hidden' : ''}"></div>`);
    self.field.append(`<input type="hidden" name="${this.name}">`);
    this.input = self.field.find('input');

    ace.require('ace/ext/language_tools');

    this.editor = ace.edit(self.field.find('.myself-form-field-ace-editor')[0]);
    this.editor.session.setMode('ace/mode/' + this.mode);
    this.editor.setTheme('ace/theme/dracula');
    this.editor.setKeyboardHandler('ace/keyboard/vscode');
    this.editor.session.on('change', function () {
      self.input.val(self.editor.getValue());
    });
    this.editor.setOptions({
      enableBasicAutocompletion: true,
      enableLiveAutocompletion: true
    });

    if (!this.initialHidden) {
      this.editor.focus();
    }

    this.setValue(this.defaultValue);
  }

}

FramelixFormField.classReferences['MyselfFormFieldAce'] = MyselfFormFieldAce;
/**
 * Media browser
 */

class MyselfFormFieldMediaBrowser extends FramelixFormField {
  constructor(...args) {
    super(...args);

    _defineProperty(this, "allowedExtensions", null);

    _defineProperty(this, "signedGetBrowserUrl", void 0);

    _defineProperty(this, "modal", void 0);

    _defineProperty(this, "openBrowserBtn", void 0);

    _defineProperty(this, "browserContent", void 0);

    _defineProperty(this, "selectedInfoContainer", void 0);

    _defineProperty(this, "selectedEntriesContainer", void 0);

    _defineProperty(this, "unselectedEntriesContainer", void 0);

    _defineProperty(this, "multiple", false);

    _defineProperty(this, "unfoldSelectedFolders", false);
  }

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  async setValue(value, isUserChange = false) {
    this.selectedInfoContainer.empty();

    if (value) {
      const self = this;
      if (typeof value !== 'object') value = [value];

      for (let i in value) {
        self.selectedInfoContainer.append('<input type="hidden" name="' + self.name + (self.multiple ? '[]' : '') + '" value="' + value[i] + '">');
      }
    }

    this.selectedInfoContainer.append(FramelixLang.get('__myself_mediabrowser_files_selected__', [Framelix.countObjectKeys(value)]));
    this.triggerChange(this.field, isUserChange);
  }
  /**
   * Get value for this field
   * @return {string[]|string|null}
   */


  getValue() {
    let arr = [];
    this.selectedInfoContainer.find('input[type=\'hidden\']').each(function () {
      arr.push(this.value);
    });
    if (!arr.length) return null;
    if (!this.multiple) return arr[0];
    return arr;
  }
  /**
   * Upload file
   * @param {File} file
   * @param {string=} replaceId The id to replace with
   * @return {Promise<void>}
   */


  async uploadFile(file, replaceId) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('replaceId', replaceId || '');
    const request = FramelixRequest.request('post', this.signedGetBrowserUrl, null, formData, true);
    await request.finished;
    const result = await request.getJson();

    if (result === true) {
      FramelixToast.success('__myself_mediabrowser_uploaded__');
    } else {
      FramelixToast.error(FramelixLang.get('__myself_mediabrowser_upload_failed__', [result + ':' + (this.allowedExtensions ? this.allowedExtensions.join(', ') : '*')]));
    }
  }
  /**
   * Reload current browser context
   */


  async reload() {
    this.browserContent.html('<div class="framelix-loading"></div>');
    const selectedIds = this.getValue();
    this.browserContent.html(await FramelixApi.callPhpMethod(this.signedGetBrowserUrl, {
      'selectedIds': selectedIds
    }));
    this.selectedEntriesContainer = this.browserContent.find('.myself-media-browser-entries[data-type=\'selected\']');
    this.unselectedEntriesContainer = this.browserContent.find('.myself-media-browser-entries[data-type=\'unselected\']');
    await FramelixDom.includeCompiledFile('Framelix', 'js', 'sortablejs', 'Sortable');
    new Sortable(this.selectedEntriesContainer[0]);
  }
  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */


  async renderInternal() {
    const self = this;
    await super.renderInternal();
    this.openBrowserBtn = $('<button class="framelix-button framelix-button-primary" data-icon-left="perm_media" type="button">' + FramelixLang.get('__myself_open_media_browser__') + '</button>');
    this.selectedInfoContainer = $('<div class="myself-media-browser"></div>');
    this.field.append(this.openBrowserBtn);
    this.field.append(this.selectedInfoContainer);
    let replaceId = null;
    this.openBrowserBtn.on('click', async function () {
      self.browserContent = $('<div class="myself-media-browser">');
      const buttonsRow = $('<div></div>');
      const saveBtn = $(`<button class="framelix-button framelix-button-success" data-icon-left="check"></button>`).text(FramelixLang.get('__myself_mediabrowser_accept__')).on('click', function () {
        let values = [];
        self.selectedEntriesContainer.children(function () {
          values.push($(this).attr('data-id'));
        });

        if (!self.multiple) {
          values = values.shift();
        }

        self.setValue(values);
        self.modal.close();
      });
      buttonsRow.append(saveBtn);
      self.modal = FramelixModal.show(self.browserContent, buttonsRow, true);
      self.modal.closed.then(function () {
        self.modal = null;
        self.browserContent = null;
      });
      self.modal.contentContainer.addClass('myself-edit-font');
      self.browserContent.toggleClass('myself-media-browser-multiple', self.multiple);
      self.browserContent.on('click', '.myself-media-browser-entry-options-icon', function (ev) {
        ev.stopPropagation();
        FramelixPopup.destroyAll();
        FramelixPopup.showPopup(this, $(this).next('.myself-media-browser-entry-options').clone().removeClass('hidden'));
      });
      self.browserContent.on('change', '.myself-media-browser-entry-select', async function () {
        let entry = $(this).closest('.myself-media-browser-entry');
        let id = entry.attr('data-id');

        if (this.checked && self.unfoldSelectedFolders && entry.hasClass('myself-media-browser-entry-folder')) {
          this.checked = false;
          entry.addClass('framelix-pulse');
          Framelix.showProgressBar(-1);
          const entries = await FramelixApi.callPhpMethod(self.signedGetBrowserUrl, {
            'unfoldFolder': id
          });
          entry.removeClass('framelix-pulse');
          Framelix.showProgressBar(null);

          if (entries) {
            const entriesDiv = $('<div>').html(entries);
            const selectedDiv = self.selectedEntriesContainer;
            entriesDiv.children().each(function () {
              const id = $(this).attr('data-id');

              if (!selectedDiv.children('[data-id=\'' + id + '\']').length) {
                selectedDiv.append(this);
              }
            });
          }

          return;
        }

        const checked = this.checked;

        if (checked) {
          if (!self.multiple) {
            self.selectedEntriesContainer.children().each(function () {
              self.unselectedEntriesContainer.append(this);
            });
          }

          self.selectedEntriesContainer.append(entry);

          if (!self.multiple) {
            saveBtn.trigger('click');
          }
        } else {
          self.unselectedEntriesContainer.append(entry);
        }
      });
      self.browserContent.on('click', '.myself-media-browser-entry-selectable', async function (ev) {
        if ($(ev.target).is('.myself-media-browser-entry-select, button, .myself-media-browser-entry-load-url')) return;
        const input = $(this).find('.myself-media-browser-entry-select');
        input[0].checked = !input[0].checked;
        input.trigger('change');
      });
      self.browserContent.on('click', '.myself-media-browser-entry-load-url', async function (ev) {
        const entry = $(this).closest('.myself-media-browser-entry');
        self.signedGetBrowserUrl = entry.attr('data-load-url');
        self.reload();
      });
      self.modal.container.on('dragover', async function (ev) {
        ev.preventDefault();
      });
      self.modal.container.on('drop', async function (ev) {
        ev.preventDefault();
        if (!ev.dataTransfer.files.length) return;
        replaceId = null;

        for (let i = 0; i < ev.dataTransfer.files.length; i++) {
          await self.uploadFile(ev.dataTransfer.files[i]);
        }

        self.reload();
      });
      self.browserContent.on('click', '.myself-media-browser-entry-create-folder', async function (ev) {
        const newName = (await FramelixModal.prompt(null).closed).promptResult;

        if (newName) {
          await FramelixApi.callPhpMethod($(this).attr('data-create-folder'), {
            'folderName': newName
          });
          self.reload();
        }
      });
      self.browserContent.on('change', '.myself-media-browser-replace-file', async function (ev) {
        if (!ev.target.files) return;

        for (let i = 0; i < ev.target.files.length; i++) {
          await self.uploadFile(ev.target.files[i], $(this).attr('data-replace-id'));
        }

        self.reload();
      });
      self.modal.container.on('change', '.myself-media-browser-entry-upload', async function (ev) {
        if (!ev.target.files) return;

        for (let i = 0; i < ev.target.files.length; i++) {
          await self.uploadFile(ev.target.files[i]);
        }

        self.reload();
      });
      $(document).off('click.mediabrowser-delete-folder').on('click.mediabrowser-delete-folder', '.myself-media-browser-entry-delete[data-delete-folder-url]', async function (ev) {
        ev.stopPropagation();
        FramelixPopup.destroyAll();
        let result = (await FramelixModal.prompt('__myself_mediabrowser_delete_folder_securequestion__').closed).promptResult;

        if (result !== null && result.toLowerCase() === 'yes') {
          if (await FramelixApi.callPhpMethod($(this).attr('data-delete-folder-url'))) {
            self.reload();
          }
        }
      });
      $(document).off('click.mediabrowser-delete-file').on('click.mediabrowser-delete-file', '.myself-media-browser-entry-delete[data-delete-file-url]', async function (ev) {
        ev.stopPropagation();
        FramelixPopup.destroyAll();

        if ((await FramelixModal.confirm('__framelix_sure__').closed).confirmed) {
          if (await FramelixApi.callPhpMethod($(this).attr('data-delete-file-url'))) {
            self.reload();
          }
        }
      });
      $(document).off('click.mediabrowser-replace').on('click.mediabrowser-replace', '.myself-media-browser-entry-replace', async function (ev) {
        ev.stopPropagation();
        FramelixPopup.destroyAll();
        replaceId = $(this).attr('data-replace-id');
        const label = $('.myself-media-browser-replace-file');
        label.attr('data-replace-id', replaceId);
        label.trigger('click');
      });
      $(document).off('click.mediabrowser-rename').on('click.mediabrowser-rename', '.myself-media-browser-entry-rename', async function (ev) {
        ev.stopPropagation();
        FramelixPopup.destroyAll();
        const newName = (await FramelixModal.prompt(null, $(this).attr('data-title')).closed).promptResult;

        if (newName) {
          if (await FramelixApi.callPhpMethod($(this).attr('data-rename-url'), {
            'newName': newName
          })) {
            self.reload();
          }
        }
      });
      self.reload();
    });
    await this.setValue(this.defaultValue);
  }

}

FramelixFormField.classReferences['MyselfFormFieldMediaBrowser'] = MyselfFormFieldMediaBrowser;