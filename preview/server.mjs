import http from 'node:http';
import { createReadStream } from 'node:fs';
import { stat } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const port = Number(process.env.PORT || 3000);
const host = '0.0.0.0';
const mimeTypes = {
	'.html': 'text/html; charset=utf-8',
	'.css': 'text/css; charset=utf-8',
	'.js': 'text/javascript; charset=utf-8',
	'.mjs': 'text/javascript; charset=utf-8',
	'.json': 'application/json; charset=utf-8',
	'.svg': 'image/svg+xml',
	'.png': 'image/png',
	'.jpg': 'image/jpeg',
	'.jpeg': 'image/jpeg',
	'.webp': 'image/webp',
	'.gif': 'image/gif',
	'.ico': 'image/x-icon',
};

const server = http.createServer(async (request, response) => {
	try {
		const url = new URL(request.url, `http://${request.headers.host || 'localhost'}`);
		let pathname;
		try {
			pathname = decodeURIComponent(url.pathname);
		} catch {
			response.writeHead(400).end('Bad request');
			return;
		}

		const requestPath = pathname === '/' ? '/preview/index.html' : pathname;
		let filePath = path.resolve(root, `.${requestPath}`);
		if (filePath !== root && !filePath.startsWith(`${root}${path.sep}`)) {
			response.writeHead(403).end('Forbidden');
			return;
		}

		let fileStat;
		try {
			fileStat = await stat(filePath);
			if (fileStat.isDirectory()) {
				filePath = path.join(filePath, 'index.html');
				fileStat = await stat(filePath);
			}
		} catch {
			response.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' }).end('Not found');
			return;
		}

		const extension = path.extname(filePath).toLowerCase();
		const contentType = mimeTypes[extension] || 'application/octet-stream';
		const isDocument = ['.html', '.css', '.js', '.mjs', '.json'].includes(extension);
		response.writeHead(200, {
			'Content-Type': contentType,
			'Content-Length': fileStat.size,
			'Cache-Control': isDocument ? 'no-cache' : 'public, max-age=3600',
			'X-Content-Type-Options': 'nosniff',
		});
		if (request.method === 'HEAD') {
			response.end();
			return;
		}
		createReadStream(filePath).pipe(response);
	} catch (error) {
		console.error(error);
		response.writeHead(500).end('Internal server error');
	}
});

server.listen(port, host, () => {
	console.log(`Andalan Mutu Energi preview available at http://${host}:${port}`);
});
