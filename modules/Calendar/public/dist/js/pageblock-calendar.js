'use strict'

class CalendarPageBlocksCalendar extends MyselfPageBlocks {
  /**
   * Init block
   */
  initBlock () {
    this.blockContainer.on(FramelixFormField.EVENT_CHANGE, '.calendar-pageblocks-calendar-month-select', function () {
      const field = FramelixFormField.getFieldByName($(this), 'month')
      window.location.href = field.getValue()
    })
    this.blockContainer.on('click', 'td[data-modal]', function (ev) {
      FramelixModal.callPhpMethod($(this).attr('data-modal'))
    })
  }

}