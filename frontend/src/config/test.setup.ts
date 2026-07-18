// Test environment setup

// jsdom lacks ResizeObserver, which antd/rc-component observe on mount.
/* eslint-disable class-methods-use-this -- no-op stub methods */
class ResizeObserverStub {
  observe(): void {}

  unobserve(): void {}

  disconnect(): void {}
}
/* eslint-enable class-methods-use-this */

globalThis.ResizeObserver = ResizeObserverStub as unknown as typeof ResizeObserver
