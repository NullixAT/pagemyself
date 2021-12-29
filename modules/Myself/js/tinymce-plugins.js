tinymce.PluginManager.add('myself', function (editor, url) {
  /* Add a button that opens a window */
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
  /* Add a button that opens a window */
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

  return {
    getMetadata: function () {
      return {
        name: 'Live Editable Text'
      }
    }
  }
})

tinymce.PluginManager.get('image', function (editor, url) {
  /* Add a button that opens a window */
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
})