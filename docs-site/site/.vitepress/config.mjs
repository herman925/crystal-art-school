import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Crystal Art School',
  description: 'How to update the Crystal Art School website.',
  lang: 'en',
  cleanUrls: true,
  base: '/',
  head: [['meta', { name: 'robots', content: 'noindex, nofollow' }]],
  themeConfig: {
    siteTitle: 'Site guide',
    outline: { level: [2, 3], label: 'On this page' },
    search: { provider: 'local' },
    sidebar: [
      {
        text: 'Before launch',
        items: [
          { text: 'Before launch: what we need from you', link: '/before-launch' },
          { text: 'Launch call agenda', link: '/launch-call-agenda' }
        ]
      },
      {
        text: 'Start here',
        items: [
          { text: 'Your site guide', link: '/' },
          { text: 'Logging in', link: '/logging-in' },
          { text: 'What you can edit', link: '/what-you-can-edit' }
        ]
      },
      {
        text: 'Editing the site',
        items: [
          { text: 'Logo, signature and contact', link: '/brand-and-contact' },
          { text: 'The menu across the top', link: '/menu' },
          { text: 'The homepage banner', link: '/home-hero' },
          { text: 'The rest of the homepage', link: '/homepage-cards' },
          { text: 'The About page', link: '/about-page' },
          { text: 'Questions and answers', link: '/faq' },
          { text: 'Student comments', link: '/testimonials' },
          { text: 'Courses', link: '/courses' },
          { text: 'Lessons', link: '/lessons' },
          { text: 'The gallery', link: '/gallery' },
          { text: 'Student work', link: '/showcase' },
          { text: 'Free videos', link: '/free-videos' },
          { text: 'Blog articles', link: '/blog' }
        ]
      },
      {
        text: 'Money and access — draft',
        items: [
          { text: 'Start with the safety checklist', link: '/money-guide-draft' },
          { text: 'Course prices — draft', link: '/course-prices-draft' },
          { text: 'Coupons — draft', link: '/coupons-draft' },
          { text: 'Sales and exports — draft', link: '/sales-draft' },
          { text: 'Course access — draft', link: '/course-access-draft' },
          { text: 'Troubleshooting — draft', link: '/money-help-draft' }
        ]
      },
      {
        text: 'Pages that look after themselves',
        items: [
          { text: 'Topic, date and search pages', link: '/archives' },
          { text: 'Signing in, baskets and the dashboard', link: '/accounts' }
        ]
      },
      { text: 'Limits', items: [{ text: 'What is fixed, and why', link: '/limits' }] }
    ],
    docFooter: { prev: false, next: false },
    footer: { message: 'Internal — please do not share this address.' },
    // target MUST stay: VitePress's click handler passes any <a target> straight to the
    // browser, and that is the only supported way to get a real document navigation out of
    // the SPA. Without it, /logout.php is fetched and swapped in as a page instead of being
    // navigated to, so clicking Sign out never leaves the guide. _self keeps it in this tab.
    nav: [{ text: 'Sign out', link: '/logout.php', target: '_self', noIcon: true }]
  },
  vite: { build: { chunkSizeWarningLimit: 2000 } }
})
