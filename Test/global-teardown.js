async function globalTeardown(config) {
  // Add any cleanup logic here if needed
  // nothing to do here - we're not creating any resources that need to be cleaned up
  console.log('Global teardown completed');
}

module.exports = globalTeardown;