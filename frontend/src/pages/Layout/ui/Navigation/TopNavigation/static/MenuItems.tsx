export interface ProductDetail {
  key: string
  label: React.ReactNode // Change React.ReactHTMLElement to React.ReactNode for better compatibility
  title: string
}

export const items: Array<ProductDetail> = [
  {
    key: 'bit-form',
    label: (
      <a href="https://bitapps.pro/bit-form" target="_blank" rel="noreferrer">
        Bit Form
      </a>
    ),
    title: 'Contact Form Builder Plugin'
  },
  {
    key: 'bit-integrations',
    label: (
      <a href="https://bitapps.pro/bit-integrations" target="_blank" rel="noreferrer">
        Bit Integrations
      </a>
    ),
    title:
      'Best Automation Plugin for WordPress. Automate 210+ (highest in WordPress) Individual Platforms.'
  },
  {
    key: 'bit-social',
    label: (
      <a href="https://bitapps.pro/bit-social" target="_blank" rel="noreferrer">
        Bit Social
      </a>
    ),
    title: 'Auto Post Scheduler & Poster for Blog to Social Media Share'
  },
  {
    key: 'bit-assist',
    label: (
      <a href="https://bitapps.pro/bit-assist" target="_blank" rel="noreferrer">
        Bit Assist
      </a>
    ),
    title:
      'Customer Support Button with SMS Call Button, Click to Chat Messenger, Live Chat Support Chat Button'
  },
  {
    key: 'bit-flows',
    label: (
      <a href="https://bit-flows.com" target="_blank" rel="noreferrer">
        Bit Flows
      </a>
    ),
    title: 'An advanced integration plugin for your WordPress website.'
  }
]
