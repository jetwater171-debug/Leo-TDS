const fs = require('fs');
const path = require('path');
const { Client } = require('pg');

// Read DATABASE_URL from process.env or .env file
let databaseUrl = process.env.DATABASE_URL;

if (!databaseUrl) {
  // Try reading .env or .env.local
  const envPath = path.join(__dirname, '../../.env.local');
  if (fs.existsSync(envPath)) {
    const envContent = fs.readFileSync(envPath, 'utf8');
    const match = envContent.match(/DATABASE_URL=["']?([^"'\n\r]+)/);
    if (match) databaseUrl = match[1];
  }
}

if (!databaseUrl) {
  console.error('Erro: DATABASE_URL não definida em process.env nem em .env.local');
  process.exit(1);
}

const botsFilePath = path.join(__dirname, '../../legacy/bases/bots.txt');
if (!fs.existsSync(botsFilePath)) {
  console.error(`Erro: Arquivo bots.txt não encontrado em: ${botsFilePath}`);
  process.exit(1);
}

async function run() {
  console.log('Iniciando importação de bots...');
  const client = new Client({
    connectionString: databaseUrl,
    ssl: { rejectUnauthorized: false }
  });

  await client.connect();
  console.log('Conectado ao PostgreSQL.');

  // Ensure tables exist by running a lightweight table creation check
  await client.query(`
    CREATE TABLE IF NOT EXISTS ip_blacklist (
      id SERIAL PRIMARY KEY,
      network CIDR NOT NULL UNIQUE
    );
    CREATE INDEX IF NOT EXISTS idx_ip_blacklist_network ON ip_blacklist USING gist (network);
  `);

  console.log('Lendo bots.txt...');
  const fileContent = fs.readFileSync(botsFilePath, 'utf8');
  const lines = fileContent.split(/\r?\n/).map(l => l.trim()).filter(l => l && !l.startsWith('#'));

  console.log(`Total de subnets encontradas: ${lines.length}`);
  
  const CHUNK_SIZE = 1000;
  let imported = 0;

  for (let i = 0; i < lines.length; i += CHUNK_SIZE) {
    const chunk = lines.slice(i, i + CHUNK_SIZE);
    
    // Build batch insert query
    const values = [];
    const valuePlaceholders = chunk.map((_, idx) => {
      values.push(_);
      return `($${idx + 1})`;
    }).join(', ');

    const query = `
      INSERT INTO ip_blacklist (network) 
      VALUES ${valuePlaceholders}
      ON CONFLICT (network) DO NOTHING
    `;

    try {
      await client.query(query, values);
      imported += chunk.length;
      process.stdout.write(`Progresso: ${imported}/${lines.length} (${Math.round((imported / lines.length) * 100)}%)\r`);
    } catch (err) {
      console.error(`\nErro ao inserir chunk a partir do índice ${i}:`, err.message);
    }
  }

  console.log('\nImportação concluída com sucesso!');
  await client.end();
}

run().catch(console.error);
