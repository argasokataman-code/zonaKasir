#!/usr/bin/env node
import { createConnection } from 'mysql2/promise';
import pg from 'pg';

const DB_TYPE = process.env.DB_TYPE || 'pgsql';
const cfg = {
  host: process.env.DB_HOST || '127.0.0.1',
  port: parseInt(process.env.DB_PORT || (DB_TYPE === 'mysql' ? '3306' : '5432')),
  database: process.env.DB_DATABASE || 'zonakasir',
  user: process.env.DB_USER || 'zonakasir',
  password: process.env.DB_PASSWORD || 'secret',
};

let pool = null;

async function getPool() {
  if (pool) return pool;
  if (DB_TYPE === 'mysql') {
    pool = await createConnection({ host: cfg.host, port: cfg.port, database: cfg.database, user: cfg.user, password: cfg.password });
  } else {
    pool = new pg.Pool({ host: cfg.host, port: cfg.port, database: cfg.database, user: cfg.user, password: cfg.password });
  }
  return pool;
}

async function query(sql) {
  const c = await getPool();
  if (DB_TYPE === 'mysql') {
    const [rows] = await c.execute(sql);
    return rows;
  } else {
    const res = await c.query(sql);
    return res.rows;
  }
}

async function listTables() {
  if (DB_TYPE === 'mysql') {
    return query(`SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '${cfg.database}' ORDER BY TABLE_NAME`);
  }
  return query(`SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name`);
}

async function describeTable(table) {
  if (DB_TYPE === 'mysql') {
    return query(`SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '${cfg.database}' AND TABLE_NAME = '${table.replace(/[^a-z0-9_]/gi, '')}' ORDER BY ORDINAL_POSITION`);
  }
  return query(`SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '${table.replace(/[^a-z0-9_]/gi, '')}' ORDER BY ordinal_position`);
}

function textContent(text) {
  return { content: [{ type: 'text', text }] };
}

function errorContent(err) {
  return { content: [{ type: 'text', text: err.message || String(err) }], isError: true };
}

let buf = '';
process.stdin.on('data', (chunk) => {
  buf += chunk.toString();
  const lines = buf.split('\n');
  buf = lines.pop() || '';
  for (const line of lines) {
    const t = line.trim();
    if (t) handleRequest(t).catch(() => {});
  }
});

function write(msg) {
  process.stdout.write(JSON.stringify(msg) + '\n');
}

async function handleRequest(raw) {
  let req;
  try { req = JSON.parse(raw); } catch { return; }
  const { id, method, params } = req;

  try {
    if (method === 'initialize') {
      write({ jsonrpc: '2.0', id, result: { protocolVersion: '2024-11-05', capabilities: { tools: {} }, serverInfo: { name: 'db-server', version: '1.0.0' } } });
    } else if (method === 'notifications/initialized' || method === 'notifications/cancelled') {
      // noop
    } else if (method === 'tools/list') {
      write({ jsonrpc: '2.0', id, result: {
        tools: [
          { name: 'query', description: 'Execute SQL query. Returns array of rows as JSON.', inputSchema: { type: 'object', properties: { sql: { type: 'string', description: 'SQL query (SELECT only for safety)' } }, required: ['sql'] } },
          { name: 'list_tables', description: 'List all tables in current database.', inputSchema: { type: 'object', properties: {} } },
          { name: 'describe_table', description: 'Show column schema for a table.', inputSchema: { type: 'object', properties: { table: { type: 'string', description: 'Table name' } }, required: ['table'] } },
        ]
      } });
    } else if (method === 'tools/call') {
      const { name, arguments: args } = params;
      if (name === 'query') {
        const sql = args.sql.trim().toLowerCase();
        // Only allow SELECT for safety
        if (!sql.startsWith('select')) {
          write({ jsonrpc: '2.0', id, result: errorContent(new Error('Only SELECT queries allowed for safety')) });
          return;
        }
        const rows = await query(args.sql);
        write({ jsonrpc: '2.0', id, result: textContent(JSON.stringify(rows, null, 2)) });
      } else if (name === 'list_tables') {
        const rows = await listTables();
        write({ jsonrpc: '2.0', id, result: textContent(JSON.stringify(rows, null, 2)) });
      } else if (name === 'describe_table') {
        const rows = await describeTable(args.table);
        write({ jsonrpc: '2.0', id, result: textContent(JSON.stringify(rows, null, 2)) });
      } else {
        write({ jsonrpc: '2.0', id, result: errorContent(new Error(`Unknown tool: ${name}`)) });
      }
    } else {
      write({ jsonrpc: '2.0', id, result: errorContent(new Error(`Unknown method: ${method}`)) });
    }
  } catch (err) {
    write({ jsonrpc: '2.0', id, result: errorContent(err) });
  }
}
