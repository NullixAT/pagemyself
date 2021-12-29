class GuestbookPageBlocksGuestbook extends MyselfPageBlocks {
  /**
   * Init block
   */
  initBlock () {
    this.blockContainer.on('click', '[data-delete-url]', async function () {
      const el = $(this)
      if (await FramelixModal.confirm('__framelix_sure__').confirmed) {
        await FramelixApi.callPhpMethod($(this).attr('data-delete-url'))
        el.closest('.guestbook-pageblocks-guestbook-entry').remove()
      }
    })
    this.blockContainer.on('click', '[data-showhide-url]', async function () {
      await FramelixApi.callPhpMethod($(this).attr('data-showhide-url'))
      location.reload()
    })
  }
}