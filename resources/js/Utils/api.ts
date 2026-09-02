const errorMessages = {
  weather_not_found: 'Cidade não encontrada. Verifique a busca e tente novamente.',
  weather_rate_limited: 'O serviço de clima está ocupado. Tente novamente em instantes.',
  weather_timeout: 'A consulta demorou mais que o esperado. Tente novamente.',
  weather_network_error: 'Não foi possível conectar ao serviço de clima. Verifique sua conexão.',
  weather_unavailable: 'Não foi possível atualizar o clima agora. Tente novamente.',
} as const;

type ApiErrorCode = keyof typeof errorMessages;

interface ApiErrorResponse {
  code?: unknown;
}

export class ApiRequestError extends Error {}

function isApiErrorCode(value: unknown): value is ApiErrorCode {
  return typeof value === 'string' && value in errorMessages;
}

export async function apiRequestError(
  response: Response,
  fallbackMessage: string,
): Promise<ApiRequestError> {
  try {
    const payload = await response.json() as ApiErrorResponse;

    if (isApiErrorCode(payload.code)) {
      return new ApiRequestError(errorMessages[payload.code]);
    }
  } catch {
    // Invalid error payloads intentionally fall back to a local, safe message.
  }

  return new ApiRequestError(fallbackMessage);
}

export function safeRequestErrorMessage(reason: unknown, fallbackMessage: string): string {
  return reason instanceof ApiRequestError ? reason.message : fallbackMessage;
}
