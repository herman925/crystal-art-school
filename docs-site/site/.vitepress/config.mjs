import { defineConfig } from 'vitepress'

const admin = process.env.DOCS_AUDIENCE === 'admin'

export default defineConfig({
  title: 'Crystal Art School',
  description: 'Crystal’s guide to updating her website.',
  lang: 'en',
  cleanUrls: true,
  srcExclude: admin ? [] : ['admin/**'],
  base: '/',
  head: [['meta', { name: 'robots', content: 'noindex, nofollow' }]],
  themeConfig: {
    siteTitle: admin ? 'Site guide · Herman' : 'Crystal’s site guide',
    outline: { level: [2, 3], label: 'On this page' },
    search: { provider: 'local' },
    sidebar: [
      { text: 'Start here', items: [
        { text: 'Choose your task', link: '/' },
        { text: 'Try your website before launch', link: '/website-review' },
        { text: 'Signing in and out', link: '/logging-in' },
        { text: 'Saving and publishing', link: '/editing-basics' },
        { text: 'Pictures and files', link: '/pictures-and-files' },
        { text: 'Find the right editor', link: '/what-you-can-edit' }
      ] },
      { text: 'Website pages', items: [
        { text: 'Homepage banner', link: '/home-hero' },
        { text: 'Homepage cards', link: '/homepage-cards' },
        { text: 'About page', link: '/about-page' },
        { text: 'Your certificates and honours', link: '/certificates' },
        { text: 'Logo, signature and contact', link: '/brand-and-contact' },
        { text: 'Gallery and Etsy links', link: '/gallery' },
        { text: 'Student work', link: '/showcase' },
        { text: 'Student comments', link: '/testimonials' },
        { text: 'Questions and answers', link: '/faq' },
        { text: 'Free videos', link: '/free-videos' },
        { text: 'Blog articles', link: '/blog' }
      ] },
      { text: 'Courses and lessons', items: [
        { text: 'Course content and photographs', link: '/courses' },
        { text: 'Lessons, notes and reference photos', link: '/lessons' }
      ] },
      { text: 'Prices and student help', items: [
        { text: 'Choose the right tool', link: '/money-guide' },
        { text: 'Course prices', link: '/course-prices' },
        { text: 'Coupons', link: '/coupons' },
        { text: 'Sales and orders', link: '/sales' },
        { text: 'Course access', link: '/course-access' },
        { text: 'Money and access warnings', link: '/money-help' },
        { text: 'What students see', link: '/accounts' }
      ] },
      { text: 'Help and preparation', items: [
        { text: 'If something is not working', link: '/help' },
        { text: 'Privacy and student permissions', link: '/privacy-and-permissions' },
        { text: 'What stays with the site administrator', link: '/limits' },
        { text: 'Request a menu change', link: '/menu' },
        { text: 'Pages that fill themselves', link: '/archives' },
        { text: 'What to prepare before launch', link: '/before-launch' }
      ] },
      ...(admin ? [{ text: 'Administrator · Herman only', items: [
        { text: 'Administrator operating notes', link: '/admin/' },
        { text: 'Account handover', link: '/admin/handover' },
        { text: 'Payments, refunds and disputes', link: '/admin/payments-and-access' },
        { text: 'Release and recovery', link: '/admin/release-and-recovery' },
        { text: 'Launch checks', link: '/admin/launch-checks' },
        { text: 'Content map', link: '/admin/content-map' },
        { text: 'Launch call agenda', link: '/admin/launch-call-agenda' }
      ] }] : [])
    ],
    docFooter: { prev: false, next: false },
    footer: { message: 'Private working guide — please do not share the login.' },
    nav: [
      ...(admin ? [{ text: 'Administrator', link: '/admin/' }] : []),
      { text: 'Open website editor', link: 'https://crystalau.com/wp-admin/', target: '_blank' },
      // Full navigation is required: the SPA must not fetch logout as a content page.
      { text: 'Sign out', link: '/logout.php', target: '_self', noIcon: true }
    ]
  },
  vite: { build: { chunkSizeWarningLimit: 2000 } }
})
