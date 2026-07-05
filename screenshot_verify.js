import { chromium } from 'playwright';

async function run() {
    const browser = await chromium.launch({ headless: true });
    
    async function capturePage(urlPath, name) {
        const context = await browser.newContext({
            viewport: { width: 1280, height: 1000 }
        });
        const page = await context.newPage();
        await page.goto('http://127.0.0.1:8000/login');
        await page.fill('#email', 'god@unimar.edu.ve');
        await page.fill('#password', '--god--');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        
        await page.goto(`http://127.0.0.1:8000/${urlPath}`);
        await page.waitForTimeout(3000);
        
        await page.screenshot({ 
            path: `/home/regna/.gemini/antigravity-cli/brain/43048279-b3bb-4ebc-ac68-3e6b4631a18d/${name}_desktop_verified.png`, 
            fullPage: true 
        });
        
        // Mobile view
        const contextMobile = await browser.newContext({
            viewport: { width: 375, height: 1000 }
        });
        const pageMobile = await contextMobile.newPage();
        await pageMobile.goto('http://127.0.0.1:8000/login');
        await pageMobile.fill('#email', 'god@unimar.edu.ve');
        await pageMobile.fill('#password', '--god--');
        await pageMobile.click('button[type="submit"]');
        await pageMobile.waitForTimeout(2000);
        await pageMobile.goto(`http://127.0.0.1:8000/${urlPath}`);
        await pageMobile.waitForTimeout(3000);
        await pageMobile.screenshot({ 
            path: `/home/regna/.gemini/antigravity-cli/brain/43048279-b3bb-4ebc-ac68-3e6b4631a18d/${name}_mobile_verified.png`, 
            fullPage: true 
        });
        
        console.log(`Saved verified screenshots for ${name}`);
        await context.close();
        await contextMobile.close();
    }

    await capturePage('catalog', 'catalog');
    await capturePage('admin/users', 'users_crud');

    await browser.close();
}

run().catch(console.error);
