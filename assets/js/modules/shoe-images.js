/**
 * SneakX - Real Shoe Images from Unsplash
 * Replaces emoji placeholders with actual shoe photography
 */

export const SHOE_PRODUCTS = [
    {
        id: 1,
        name: "Air Jordan Retro 100",
        brand: "Nike",
        price: 189.99,
        promo_price: 149.99,
        image: "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=200&q=80",
        color: "Rouge/Blanc",
        bg: "linear-gradient(135deg,#1a0900,#2e1500)"
    },
    {
        id: 2,
        name: "Ultra Boost Cloud",
        brand: "Adidas",
        price: 210.00,
        promo_price: null,
        image: "https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=200&q=80",
        color: "Bleu Marine",
        bg: "linear-gradient(135deg,#00112a,#001e44)"
    },
    {
        id: 3,
        name: "Shadow X Runner",
        brand: "New Balance",
        price: 155.00,
        promo_price: null,
        image: "https://images.unsplash.com/photo-1600269452121-4f2416e55c28?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1600269452121-4f2416e55c28?w=200&q=80",
        color: "Gris/Noir",
        bg: "linear-gradient(135deg,#0d0d0d,#1e1e1e)"
    },
    {
        id: 4,
        name: "Trail Blazer Pro",
        brand: "ASICS",
        price: 175.00,
        promo_price: 140.00,
        image: "https://images.unsplash.com/photo-1539185441755-769473a23570?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1539185441755-769473a23570?w=200&q=80",
        color: "Vert Forest",
        bg: "linear-gradient(135deg,#001408,#002811)"
    },
    {
        id: 5,
        name: "Velvet Classic High",
        brand: "Jordan",
        price: 220.00,
        promo_price: null,
        image: "https://images.unsplash.com/photo-1552346154-21d32810aba3?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1552346154-21d32810aba3?w=200&q=80",
        color: "Violet Royal",
        bg: "linear-gradient(135deg,#1a001a,#2e002e)"
    },
    {
        id: 6,
        name: "Summer Pulse",
        brand: "Puma",
        price: 130.00,
        promo_price: 99.00,
        image: "https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?w=200&q=80",
        color: "Blanc/Or",
        bg: "linear-gradient(135deg,#1a1a00,#2e2e00)"
    },
    {
        id: 7,
        name: "Coastal Runner",
        brand: "Nike",
        price: 165.00,
        promo_price: null,
        image: "https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=200&q=80",
        color: "Bleu Ciel",
        bg: "linear-gradient(135deg,#001a2e,#002e50)"
    },
    {
        id: 8,
        name: "Force Alpha",
        brand: "Nike",
        price: 195.00,
        promo_price: 165.00,
        image: "https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=200&q=80",
        color: "Blanc Pur",
        bg: "linear-gradient(135deg,#0a0a0a,#222222)"
    },
    {
        id: 9,
        name: "Neon Street Kid",
        brand: "Adidas",
        price: 140.00,
        promo_price: null,
        image: "https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=200&q=80",
        color: "Multicolor",
        bg: "linear-gradient(135deg,#0d0020,#1a0035)"
    },
    {
        id: 10,
        name: "Terra Vibe",
        brand: "Reebok",
        price: 120.00,
        promo_price: 89.00,
        image: "https://images.unsplash.com/photo-1597248881519-db089b15f9be?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1597248881519-db089b15f9be?w=200&q=80",
        color: "Terre Battue",
        bg: "linear-gradient(135deg,#1a0a00,#2e1500)"
    },
    {
        id: 11,
        name: "Phantom Rush",
        brand: "Under Armour",
        price: 180.00,
        promo_price: null,
        image: "https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=200&q=80",
        color: "Noir Mat",
        bg: "linear-gradient(135deg,#050505,#141414)"
    },
    {
        id: 12,
        name: "Glow Trainer",
        brand: "New Balance",
        price: 160.00,
        promo_price: 128.00,
        image: "https://images.unsplash.com/photo-1518002171953-a080ee817e1f?w=600&q=80",
        thumbnail: "https://images.unsplash.com/photo-1518002171953-a080ee817e1f?w=200&q=80",
        color: "Orange Fluo",
        bg: "linear-gradient(135deg,#1a0800,#321000)"
    }
];

export const HERO_SHOE_URL = "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=90";
export const HERO_SHOE_2_URL = "https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=90";
export const FEATURE_SHOE_URL = "https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=700&q=85";

export const SHOE_GALLERY = [
    "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&q=80",
    "https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=600&q=80",
    "https://images.unsplash.com/photo-1600269452121-4f2416e55c28?w=600&q=80",
    "https://images.unsplash.com/photo-1552346154-21d32810aba3?w=600&q=80",
    "https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=600&q=80",
    "https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?w=600&q=80"
];

/**
 * Get a shoe image URL by index (cycles through available images)
 */
export function getShoeImage(index = 0, size = 'medium') {
    const sizes = { small: '?w=200&q=75', medium: '?w=500&q=80', large: '?w=800&q=85' };
    const q = sizes[size] || sizes.medium;
    const imgs = SHOE_PRODUCTS.map(p => p.image.split('?')[0]);
    return imgs[index % imgs.length] + q;
}

export default { SHOE_PRODUCTS, HERO_SHOE_URL, HERO_SHOE_2_URL, FEATURE_SHOE_URL, SHOE_GALLERY, getShoeImage };
