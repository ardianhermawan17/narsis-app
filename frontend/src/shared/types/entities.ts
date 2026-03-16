export interface AuthTokenPair {
  accessToken: string
  refreshToken: string
  tokenType: string
  expiresIn: number
}

export interface User {
  id: string
  username: string
  email: string
  displayName?: string
  bio?: string
  createdAt: string
  updatedAt: string
}
