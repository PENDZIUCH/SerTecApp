const CORS_HEADERS = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization, Accept',
  'Content-Type': 'application/json',
};

export function ok(data: unknown, status = 200): Response {
  return new Response(JSON.stringify(data), { status, headers: CORS_HEADERS });
}

export function err(message: string, status = 400, errors?: Record<string, string[]>): Response {
  return new Response(JSON.stringify({ message, ...(errors && { errors }) }), { status, headers: CORS_HEADERS });
}

export function cors(): Response {
  return new Response(null, { status: 204, headers: CORS_HEADERS });
}

export function addCors(response: Response): Response {
  const headers = new Headers(response.headers);
  Object.entries(CORS_HEADERS).forEach(([k, v]) => headers.set(k, v));
  return new Response(response.body, { status: response.status, headers });
}

export function paginate(data: unknown[], total: number, page: number, perPage: number) {
  return {
    data,
    meta: {
      total,
      per_page: perPage,
      current_page: page,
      last_page: Math.ceil(total / perPage),
      from: (page - 1) * perPage + 1,
      to: Math.min(page * perPage, total),
    }
  };
}
