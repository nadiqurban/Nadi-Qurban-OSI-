// Resize the large design logos (3–6 MB) into web-sized assets in public/images.
// Run: node scripts/optimize-logos.mjs
import sharp from 'sharp';
import { mkdirSync } from 'node:fs';

mkdirSync('public/images', { recursive: true });

const sources = {
    // Square mark used in sidebar, header, login, tracking (design: uploads/Untitled design (2).png)
    'logo-mark': 'design/uploads/Untitled design (2).png',
    // Full logo for receipts / documents (design: uploads/Untitled design.png)
    'logo': 'design/uploads/Untitled design.png',
    // Certificate / tracking crest (design: untitled-design-3--mrsyf3el-a388.png)
    'logo-cert': 'design/untitled-design-3--mrsyf3el-a388.png',
};

for (const [name, src] of Object.entries(sources)) {
    for (const size of [512, 128]) {
        const img = sharp(src).resize(size, size, { fit: 'inside' });
        await img.clone().png({ compressionLevel: 9, palette: true }).toFile(`public/images/${name}-${size}.png`);
        await img.clone().webp({ quality: 88 }).toFile(`public/images/${name}-${size}.webp`);
    }
}

// Favicon (32px PNG saved as .ico container-compatible PNG; browsers accept PNG favicons).
await sharp(sources['logo-mark']).resize(32, 32).png().toFile('public/favicon.png');
await sharp(sources['logo-mark']).resize(180, 180).png().toFile('public/apple-touch-icon.png');
console.log('done');
