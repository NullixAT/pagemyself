/**
 * Ace editor
 */
class MyselfFormFieldAce extends FramelixFormField {

  /**
   * Maximal width in pixel
   * @type  {number|string|null}
   */
  maxWidth = '100%'

  /**
   * The hidden input
   * @type {Cash}
   */
  input

  /**
   * @type {ace}
   */
  editor

  /**
   * Editor language type
   * @type {string}
   */
  mode

  /**
   * Is the editor hidden and need to be enabled by clicking a button
   * @type {boolean}
   */
  initialHidden = false

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    this.editor.setValue(this.stringifyValue(value))
    this.input.val(this.editor.getValue())
    this.triggerChange(this.field, isUserChange)
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    await FramelixDom.includeResource(FramelixConfig.modulePublicUrl + '/vendor/ace-editor/ace.js', 'ace')
    await FramelixDom.includeResource(FramelixConfig.modulePublicUrl + '/vendor/ace-editor/ext-language_tools.js')
    const self = this
    if (this.initialHidden) {
      const btn = $(`<button class="framelix-button framelix-button-primary">${FramelixLang.get('__myself_form_field_ace_activate__')}</button>`)
      self.field.append(btn)
      btn.on('click', function () {
        btn.next().removeClass('hidden')
        setTimeout(function () {
          btn.remove()
          self.editor.focus()
        }, 10)
      })
    }
    self.field.append(`<div class="myself-form-field-ace-editor ${this.initialHidden ? 'hidden' : ''}"></div>`)
    self.field.append(`<input type="hidden" name="${this.name}">`)
    this.input = self.field.find('input')
    ace.require('ace/ext/language_tools')
    this.editor = ace.edit(self.field.find('.myself-form-field-ace-editor')[0])
    this.editor.session.setMode('ace/mode/' + this.mode)
    this.editor.setTheme('ace/theme/dracula')
    this.editor.setKeyboardHandler('ace/keyboard/vscode')
    this.editor.session.on('change', function () {
      self.input.val(self.editor.getValue())
    })
    this.editor.setOptions({
      enableBasicAutocompletion: true,
      enableLiveAutocompletion: true
    })
    if (!this.initialHidden) {
      this.editor.focus()
    }
    this.setValue(this.defaultValue)
  }
}

FramelixFormField.classReferences['MyselfFormFieldAce'] = MyselfFormFieldAce