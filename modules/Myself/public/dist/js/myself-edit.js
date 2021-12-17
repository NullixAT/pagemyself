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
    $(document).on('click', '.myself-select-new-page-block', async function () {
      FramelixModal.hideAll();
      const modal = await FramelixModal.request('post', MyselfEdit.pageBlockEditUrl, {
        'action': 'select-new',
        'pageId': editFrameDoc[0].querySelector('html').getAttribute('data-page')
      }, null, false, null, true);
      modal.contentContainer.addClass('myself-edit-font');
      modal.contentContainer.on(FramelixForm.EVENT_SUBMITTED, function () {
        editFrameWindow.location.reload();
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
    await frame.eval('FramelixDom').includeResource(FramelixConfig.modulePublicUrl + '/@Framelix/vendor/tinymce/tinymce.min.js', 'tinymce');
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

FramelixInit.late.push(MyselfEdit.initLate);