tinymce.PluginManager.add('myself', function (editor, url) {
  editor.ui.registry.addButton('myself-save-text', {
    text: FramelixLang.get('__framelix_save__'),
    tooltip: FramelixLang.get('__framelix_save__'),
    icon: 'save',
    onAction: async function () {
      const container = editor.myself.container
      Framelix.showProgressBar(1)
      await FramelixRequest.request('post', editor.myself.pageBlockEditUrl, { 'action': 'save-editable-content' }, {
        'storableId': container.attr('data-id'),
        'propertyName': container.attr('data-property-name'),
        'arrayKey': container.attr('data-array-key'),
        'content': editor.getContent()
      })
      FramelixToast.success('__framelix_saved__')
      Framelix.showProgressBar(null)
      editor.destroy()
    }
  })
  editor.ui.registry.addButton('myself-cancel-text', {
    text: FramelixLang.get('__framelix_cancel__'),
    tooltip: FramelixLang.get('__framelix_cancel__'),
    icon: 'cancel',
    onAction: function () {
      const container = editor.myself.container
      container.html(editor.myself.originalContent)
      editor.destroy()
    }
  })
  editor.ui.registry.addButton('myself-jump-mark', {
    text: FramelixLang.get('__myself_jump_mark__'),
    tooltip: FramelixLang.get('__myself_jump_mark_desc__'),
    icon: 'unlink',
    onAction: async function () {
      const text = await FramelixModal.prompt('__myself_jump_mark_name__').promptResult
      if (text) {
        const id = FramelixStringUtils.slugify(text).toLowerCase()
        editor.execCommand('mceInsertContent', false, '<span class="myself-jump-mark mceNonEditable" id="' + id + '" data-tooltip="' + FramelixLang.get('__myself_jump_mark_linkto__', ['#' + id]) + '">' + FramelixLang.get('__myself_jump_mark__') + ': ' + id + '</span>')
      }
    }
  })

  return {
    getMetadata: function () {
      return {
        name: 'Live Editable Text'
      }
    }
  }
})