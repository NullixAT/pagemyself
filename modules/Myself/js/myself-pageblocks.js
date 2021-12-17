class MyselfPageBlocks {

  /**
   * All page block instances
   * @type {MyselfPageBlocks[]}
   */
  static instances = []

  /**
   * The block container
   * @type {Cash}
   */
  blockContainer

  /**
   * The backend config for this block
   * @type {Object|Array}
   */
  config

  /**
   * Constructor
   * @param {Cash} blockContainer
   * @param {Object|Array} config
   */
  constructor (blockContainer, config) {
    this.config = config
    this.blockContainer = blockContainer
    this.blockContainer.attr('data-instance-id', MyselfPageBlocks.instances.length)
    MyselfPageBlocks.instances.push(this)
  }

  /**
   * Initialize the block
   */
  initBlock () {
    throw new Error('You need to override initBlock() function')
  }
}