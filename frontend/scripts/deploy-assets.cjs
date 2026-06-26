const fs = require('fs')
const path = require('path')

const src = path.join(__dirname, '../dist')
const dst = path.join(__dirname, '../../public_html')

fs.copyFileSync(path.join(src, 'index.html'), path.join(dst, 'index.html'))
fs.cpSync(path.join(src, 'assets'), path.join(dst, 'assets'), { recursive: true })

console.log('Assets copied to public_html/')
