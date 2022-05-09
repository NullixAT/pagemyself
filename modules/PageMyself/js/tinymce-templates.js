class TinymceTemplates {

  static LABEL_COLOR_BG = '__pagemyself_editor_templates_colorpicker_bg__'
  static LABEL_COLOR_TEXT = '__pagemyself_editor_templates_colorpicker_text__'

  /**
   * Get templates
   * @returns {Object<string, {html: string, title: string}>}
   */
  static getTemplates () {
    const defaultText = FramelixLang.get('__pagemyself_editor_templates_edit_text__')
    return {
      'alert': {
        'html': `<br/><div class="framelix-alert framelix-alert-customcolor" style="--color-custom-bg:{0}; --color-custom-text:{1};">${defaultText}</div><br/>`,
        'fields': [
          Object.assign(new FramelixFormFieldColor(), {
            'label': this.LABEL_COLOR_BG,
            'defaultValue': '#009dff',
            required: true
          }),
          Object.assign(new FramelixFormFieldColor(), {
            'label': this.LABEL_COLOR_TEXT,
            'defaultValue': '#fff',
            required: true
          })
        ]
      },
      'buttonLink': {
        'html': `<a class="framelix-button framelix-button-customcolor" style="--color-custom-bg:{2}; --color-custom-text:{3};" href="{0}" target="_blank">{1}</a>`,
        'fields': [
          Object.assign(new FramelixFormFieldText(), {
            'label': 'URL',
            required: true
          }),
          Object.assign(new FramelixFormFieldText(), {
            'label': 'Text',
            required: true
          }),
          Object.assign(new FramelixFormFieldColor(), {
            'label': this.LABEL_COLOR_BG,
            'defaultValue': '#009dff',
            required: true
          }),
          Object.assign(new FramelixFormFieldColor(), {
            'label': this.LABEL_COLOR_TEXT,
            'defaultValue': '#fff',
            required: true
          })
        ]
      },
      'jumpMark': {
        'html': `<div class="pagemyself-jump-mark mceNonEditable" data-id="jumpto-{1}"></div>`,
        'fields': [
          Object.assign(new FramelixFormFieldHtml(), {
            defaultValue: FramelixLang.get('__pagemyself_editor_templates_type_jumpmark_info__')
          }),
          Object.assign(new FramelixFormFieldText(), {
            'label': '__pagemyself_editor_templates_type_jumpmark__',
            required: true
          })
        ]
      },
      'emailme': {
        'html': `<button class="framelix-button framelix-button-primary" onclick='{mailonclick}'>${FramelixLang.get('__pagemyself_editor_templates_type_emailme_sendmail__')}</button>`,
        'fields': [
          Object.assign(new FramelixFormFieldEmail(), {
            'label': '__pagemyself_editor_templates_type_emailme_email__',
            required: true
          }),
          Object.assign(new FramelixFormFieldText(), {
            'label': '__pagemyself_editor_templates_type_emailme_subject__'
          })
        ]
      },
      'columns': {
        'html': ``,
        'fields': [
          Object.assign(new FramelixFormFieldNumber(), {
            'label': '__pagemyself_editor_templates_type_columns_number__',
            required: true,
            max: 5,
            defaultValue: 2
          })
        ]
      },
      'card': {
        'html': ``,
        'fields': [
          Object.assign(new FramelixFormFieldColor(), {
            'label': '__pagemyself_editor_templates_type_card_bgcolor__'
          }),
          Object.assign(new PageMyselfFormFieldMediaBrowser(), {
            'label': '__pagemyself_editor_templates_type_card_bgimage__',
            'allowedExtensions': ['png', 'jpeg', 'jpg', 'gif']
          }),
          Object.assign(new FramelixFormFieldSelect(), {
            'label': '__pagemyself_editor_templates_type_card_animation__',
            required: true,
            defaultValue: 'none',
            options: [
              ['none', '__pagemyself_editor_templates_type_card_animation_none__'],
              ['flip', '__pagemyself_editor_templates_type_card_animation_flip__'],
              ['fadein', '__pagemyself_editor_templates_type_card_animation_fadein__'],
              ['slidein', '__pagemyself_editor_templates_type_card_animation_slidein__']
            ]
          })
        ]
      }
    }
  }

  /**
   * Get template html to insert in tinymce editor
   * You can use this to ask user for some input or stuff like that that can't be handled with default fields
   * Manipulate template container to your needs
   * Do not bind events on this container, they will not be fired
   * @param {string} id The template id
   * @param {Object|null} formValues The form values, when your template has some values to enter
   * @param {Object} replacements An object with key value pairs where {key} is automatically replaced with the value before insert
   *  Add more key/value pairs if you need more replacements
   * @return {Promise<string>}
   */
  static async getTemplateHtml (id, formValues, replacements) {
    let html = this.getTemplates()[id].html
    switch (id) {
      case 'emailme': {
        const email = formValues[0]
        const subject = formValues[1]
        let mailto = 'mailto:' + email + '?subject=' + encodeURIComponent(subject)
        replacements['mailonclick'] = 'FramelixModal.show({bodyContent: atob(' + JSON.stringify(btoa(FramelixLang.get('__pagemyself_editor_templates_type_emailme_sendmailnfo__', [email]))) + '), maxWidth:600}); window.open(atob(' + JSON.stringify(btoa(mailto)) + '));'
      }
        break
      case 'columns': {
        const columns = parseInt(formValues[0])
        const container = $(`<div><div class="pagemyself-columns" data-columns="${columns}"></div></div>`)
        for (let i = 1; i <= columns; i++) {
          container.children().append(`<div class="pagemyself-column" data-color-picker="column|css|backgroundColor">Your text here</div>`)
        }
        html = container.html()
      }
        break
      case 'card': {
        const container = $(`<div><div class="pagemyself-card">
            <div class="pagemyself-card-icon"></div>
            <div class="pagemyself-card-title">${FramelixLang.get('__pagemyself_component_text_default__')}</div>
            <div class="pagemyself-card-text">${FramelixLang.get('__pagemyself_component_text_default__')}</div>
            <div class="pagemyself-card-link"><a href="#" class="framelix-button">More...</a></div>
        </div></div>`)
        const card = container.children()
        if (formValues[0]) {
          const hsl = FramelixColorUtils.rgbToHsl(...FramelixColorUtils.hexToRgb(formValues[0]))
          const inverted = FramelixColorUtils.invertColor(formValues[0], true)
          card.attr('data-background-color', 1)
          card.css('--card-text-color', inverted)
          card.css('--card-background-color-h', (hsl[0] * 360).toFixed(0) + 'deg')
          card.css('--card-background-color-s', (hsl[1] * 100).toFixed(0) + '%')
          card.css('--card-background-color-l', (hsl[2] * 100).toFixed(0) + '%')
        }
        if (formValues[1]) {
          card.attr('data-background-image', formValues[0])
          card.css('--card-background-image', formValues[0])
        }
        if (formValues[2]) {
          card.attr('data-background-fade', formValues[2])
        }
        html = container.html()
      }
        break
    }
    return html
  }
}